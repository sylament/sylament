<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\CreateAction;
use Sylament\Actions\Exceptions\ActionNotResolvableException;
use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Panels\Resources\Pages\Page as ResourcePage;
use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateArticle;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditArticle;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListArticles;

final class UnwiredResourcePage extends ResourcePage
{
    protected static string $resource = ArticleResource::class;
}

/**
 * A Unit test boots no container, so the base's registry lookup would throw —
 * this page hands back the resource the registry would have resolved.
 */
final class ResourceBoundPage extends ResourcePage
{
    protected static string $resource = ArticleResource::class;

    public function getResourceInstance(): ArticleResource
    {
        return new ArticleResource();
    }
}

/**
 * Declares its header list the way a user page does — nothing bound by hand —
 * and counts the reads so the walk's memoisation can be observed.
 */
final class HeaderActionsPage extends ResourcePage
{
    protected static string $resource = ArticleResource::class;

    public int $headerActionReads = 0;

    /**
     * @return array<Action|ActionGroup>
     */
    public function getHeaderActions(): array
    {
        $this->headerActionReads++;

        return [
            CreateAction::make(),
            ActionGroup::make([Action::make('archive')]),
        ];
    }
}

/**
 * Exposes the base's record resolver, which is protected and has no other route
 * into a Unit test.
 */
final class RecordResolvingPage extends ResourcePage
{
    protected static string $resource = ArticleResource::class;

    /**
     * @param array{name: string, arguments: array<string, mixed>, context: array<string, mixed>} $descriptor
     */
    public function resolveRecord(array $descriptor): mixed
    {
        return $this->resolveMountedActionRecord($descriptor);
    }
}

it('names the resource it belongs to through the static', function (): void {
    expect(UnwiredResourcePage::getResource())->toBe(ArticleResource::class);
});

it('registers a route as a plain page/path pair', function (): void {
    $registration = UnwiredResourcePage::route('/{record}/edit');

    expect($registration)->toBeInstanceOf(PageRegistration::class)
        ->and($registration->page)->toBe(UnwiredResourcePage::class)
        ->and($registration->path)->toBe('/{record}/edit');
});

it('fails fast when the resource registry setter never fired', function (): void {
    new UnwiredResourcePage()->getResourceInstance();
})->throws(LogicException::class, 'register it as an autowired service');

it('defaults the header actions to none', function (): void {
    expect(new UnwiredResourcePage()->getHeaderActions())->toBe([]);
});

it('binds every header action to the page and registers it under its own name', function (): void {
    // The binding is what a trigger renders from: an unbound action reaches no
    // host hook, so a `CreateAction` would render with no href at all. The
    // registration is the way back in — the request that calls a mounted action
    // renders no header.
    $page = new HeaderActionsPage();

    [$create, $group] = $page->getCachedHeaderActions();
    $grouped = $group->getFlatActions()['archive'];

    expect($create->getLiveComponent())->toBe($page)
        ->and($group->getLiveComponent())->toBe($page)
        ->and($grouped->getLiveComponent())->toBe($page)
        ->and($page->getAction('create'))->toBe($create)
        ->and($page->getAction('archive'))->toBe($grouped);
});

it('reads the declared header list once, however it is reached', function (): void {
    $page = new HeaderActionsPage();

    $page->getCachedHeaderActions();
    $page->getCachedHeaderActions();

    expect($page->getAction('nothing'))->toBeNull()
        ->and($page->headerActionReads)->toBe(1);
});

it('names the trail crumb of each shipped resource page', function (string $page, string $breadcrumb): void {
    expect(new $page()->getBreadcrumb())->toBe($breadcrumb);
})->with([
    [ListArticles::class, 'List'],
    [CreateArticle::class, 'Create'],
    [EditArticle::class, 'Edit'],
]);

it('falls back to the page title for a resource page naming no crumb of its own', function (): void {
    $named = new class extends ResourcePage {
        protected static string $resource = ArticleResource::class;

        protected static ?string $breadcrumb = 'Archive';
    };

    expect(new UnwiredResourcePage()->getBreadcrumb())->toBe('Unwired Resource Page')
        ->and($named->getBreadcrumb())->toBe('Archive');
});

/*
 * PHP inserts a trait's members over an inherited class's, so a page that
 * re-`use`s actions' `HasActions` silently replaces the base's hooks with the
 * trait's null-returning defaults — every `CreateAction` on it would then render
 * with no href and no error. `getDeclaringClass()` on a trait method answers the
 * *using* class, so this reads as the base for exactly as long as no subclass
 * re-uses the trait.
 */
it('keeps every action hook declared on the base for every shipped resource page', function (string $method, string $page): void {
    expect(new ReflectionMethod($page, $method)->getDeclaringClass()->getName())
        ->toBe(ResourcePage::class);
})->with([
    'getDefaultActionUrl',
    'getDefaultActionRecordClass',
    'getDefaultActionRecordLabel',
    'resolveMountedActionRecord',
    'callHook',
])->with([ResourcePage::class, ListRecords::class, CreateRecord::class, EditRecord::class]);

it('declines the create link for an action that names another record class', function (): void {
    // The resource registers a create page, so only the record-class guard can
    // decline the link — and the un-injected resource would fatal on getUrl().
    expect(new ResourceBoundPage()->getDefaultActionUrl(CreateAction::make()->recordClass(stdClass::class)))->toBeNull();
});

it('answers the host default record class and label off the resource', function (): void {
    $page = new ResourceBoundPage();
    $action = CreateAction::make();

    expect($page->getDefaultActionRecordClass($action))->toBe(Article::class)
        ->and($page->getDefaultActionRecordLabel($action))->toBe('article');
});

it('refuses a record key that is not an identifier before it reaches the resource', function (): void {
    // The guard runs ahead of the resource lookup, so the un-injected page never
    // has to answer one.
    expect(fn (): mixed => new RecordResolvingPage()->resolveRecord(['name' => 'edit', 'arguments' => [], 'context' => ['recordKey' => ['not', 'a', 'key']]]))
        ->toThrow(ActionNotResolvableException::class, 'Record key of type [array]');
});
