<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\CreateAction;
use Sylament\Actions\Exceptions\ActionNotResolvableException;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Enums\SubNavigationPosition;
use Sylament\Panels\Pages\Page as BasePage;
use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Panels\Resources\Pages\Page as ResourcePage;
use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;
use Sylament\Panels\Tests\Fixtures\Resources\ChapterResource;
use Sylament\Panels\Tests\Fixtures\Resources\NestedResourceRig;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateArticle;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditArticle;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditChapter;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditVerse;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListArticles;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListChapters;

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

final class PositionedResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
}

/**
 * Names no position of its own, so the resource's is what it must answer.
 */
final class ResourcePositionedPage extends ResourcePage
{
    protected static string $resource = PositionedResource::class;

    public function getResourceInstance(): PositionedResource
    {
        return new PositionedResource();
    }
}

final class PagePositionedPage extends ResourcePage
{
    protected static string $resource = PositionedResource::class;

    /**
     * Redeclared rather than inherited: `static::$subNavigationPosition = …`
     * would otherwise write the base page's property.
     */
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::End;

    public function getResourceInstance(): PositionedResource
    {
        return new PositionedResource();
    }
}

/**
 * A page of a registered resource that the resource itself registers nowhere —
 * declared here rather than under `Fixtures/`, which the kernel would register.
 */
final class UnregisteredChapterPage extends ResourcePage
{
    protected static string $resource = ChapterResource::class;
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
])->with([ResourcePage::class, ListRecords::class, CreateRecord::class, EditRecord::class]);

/*
 * The hook ladder itself sits one tier up, on the plain page every panel page
 * shares — a resource page that re-declared it would silently take the actions
 * host with it.
 */
it('keeps the convention-hook caller declared on the plain page for every shipped resource page', function (string $page): void {
    expect(new ReflectionMethod($page, 'callHook')->getDeclaringClass()->getName())
        ->toBe(BasePage::class);
})->with([ResourcePage::class, ListRecords::class, CreateRecord::class, EditRecord::class]);

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

it('lets a resource page name a sub-navigation position the resource does not', function (): void {
    expect(new ResourcePositionedPage()->getSubNavigationPosition())->toBe(SubNavigationPosition::Top)
        ->and(new PagePositionedPage()->getSubNavigationPosition())->toBe(SubNavigationPosition::End);
});

it('builds one band item per sibling page, active on the page it is asked from', function (): void {
    $entityManager = EntityManagerFactory::create();
    [$chapter] = NestedResourceRig::records($entityManager);

    $page = NestedResourceRig::page($entityManager, EditChapter::class);
    $page->mountRecord((string) $chapter->getId());

    $items = $page->generateNavigationItems([ListChapters::class, EditChapter::class]);

    expect($items)->toHaveCount(2)
        ->and(array_map(fn (NavigationItem $item): string => $item->getLabel(), $items))->toBe(['List Chapters', 'Edit Chapter'])
        ->and($items[0]->isActive())->toBeFalse()
        ->and($items[1]->isActive())->toBeTrue()
        ->and($items[1]->getUrl())->toBe(sprintf('/nest/chapters/%s/edit', $chapter->getId()));
});

it('mints a nested band url with the whole chain in it', function (): void {
    $entityManager = EntityManagerFactory::create();
    [$chapter, $verse] = NestedResourceRig::records($entityManager);

    $page = NestedResourceRig::page($entityManager, EditVerse::class);
    $page->mountRecord((string) $verse->getId(), ['chapter' => (string) $chapter->getId()]);

    // The parameters come off the page itself, so the ancestors ride along with
    // the record — a sibling of a nested page lives under the same chain.
    expect($page->getSubNavigationParameters())->toBe([
        'record' => (string) $verse->getId(),
        'chapter' => (string) $chapter->getId(),
    ])
        ->and($page->generateNavigationItems([EditVerse::class])[0]->getUrl())
        ->toBe(sprintf('/nest/chapters/%s/verses/%s/edit', $chapter->getId(), $verse->getId()));
});

it('skips a band entry for a page its resource registers nowhere', function (): void {
    $entityManager = EntityManagerFactory::create();
    [$chapter] = NestedResourceRig::records($entityManager);

    $page = NestedResourceRig::page($entityManager, EditChapter::class);
    $page->mountRecord((string) $chapter->getId());

    expect($page->generateNavigationItems([UnregisteredChapterPage::class]))->toBe([]);
});

it('refuses a record key that is not an identifier before it reaches the resource', function (): void {
    // The guard runs ahead of the resource lookup, so the un-injected page never
    // has to answer one.
    expect(fn (): mixed => new RecordResolvingPage()->resolveRecord(['name' => 'edit', 'arguments' => [], 'context' => ['recordKey' => ['not', 'a', 'key']]]))
        ->toThrow(ActionNotResolvableException::class, 'Record key of type [array]');
});

it('mints a url of its own resource, filling the record in from the page', function (): void {
    $entityManager = EntityManagerFactory::create();
    [$chapter] = NestedResourceRig::records($entityManager);

    $page = NestedResourceRig::page($entityManager, EditChapter::class);
    $page->mountRecord((string) $chapter->getId());

    // The index arm takes no record: a listing is the one page of a resource the
    // record would be a wrong parameter for.
    expect($page->getResourceUrl('edit', isAbsolute: false))->toBe(sprintf('/nest/chapters/%s/edit', $chapter->getId()))
        ->and($page->getResourceUrl(isAbsolute: false))->toBe('/nest/chapters')
        ->and($page->getResourceUrl('index', isAbsolute: false))->toBe('/nest/chapters');
});

it('threads the parent keys through a url of a nested resource page', function (): void {
    $entityManager = EntityManagerFactory::create();
    [$chapter, $verse] = NestedResourceRig::records($entityManager);

    $page = NestedResourceRig::page($entityManager, EditVerse::class);
    $page->mountRecord((string) $verse->getId(), ['chapter' => (string) $chapter->getId()]);

    expect($page->getResourceUrl('edit', isAbsolute: false))
        ->toBe(sprintf('/nest/chapters/%s/verses/%s/edit', $chapter->getId(), $verse->getId()));
});

it('lets an explicit record parameter win over the page record', function (): void {
    $entityManager = EntityManagerFactory::create();
    [$chapter] = NestedResourceRig::records($entityManager);

    $page = NestedResourceRig::page($entityManager, EditChapter::class);
    $page->mountRecord((string) $chapter->getId());

    expect($page->getResourceUrl('edit', ['record' => '7'], isAbsolute: false))->toBe('/nest/chapters/7/edit');
});
