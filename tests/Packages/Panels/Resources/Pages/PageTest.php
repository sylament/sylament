<?php

declare(strict_types=1);

use Sylament\Actions\DeleteAction;
use Sylament\Tests\Fixtures\Actions\Selectors as ActionsSelectors;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListArticles;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListScopedArticles;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

it('reaches its resource through the container, and refuses to when it was built outside it', function (): void {
    $resource = $this->createLiveComponent(ListArticles::class)->component()->getResourceInstance();

    expect($resource)->toBeInstanceOf(ArticleResource::class)
        ->and($resource->getUrl(isAbsolute: false))->toBe('/admin/articles')
        ->and(fn (): mixed => new ListArticles()->getResourceInstance())
        ->toThrow(LogicException::class, 'The resource page [' . ListArticles::class . '] was built outside the container, so it holds no resource registry; register it as an autowired service.');
});

it("mounts a registration under its resource's slug with or without a leading slash, the root path on the slug itself", function (string $route, string $path): void {
    expect($this::getContainer()->get(RouterInterface::class)->getRouteCollection()->get($route)?->getPath())->toBe($path);
})->with([
    "'/'" => ['sylament.admin.resources.articles.index', '/admin/articles'],
    "'/{record}/edit'" => ['sylament.admin.resources.articles.edit', '/admin/articles/{record}/edit'],
    "''" => ['sylament.resource-routes.resources.title-keyed-articles.index', '/resource-routes/title-keyed-articles'],
    "'{record}/edit'" => ['sylament.resource-routes.resources.title-keyed-articles.edit', '/resource-routes/title-keyed-articles/{record}/edit'],
]);

describe('breadcrumbs', function (): void {
    it("trails a resource page with its resource's listing, linked, then its own crumb", function (): void {
        $page = $this->visit('/admin/articles/create');

        expect($page->filter(SupportSelectors::BREADCRUMB)->each(static fn ($crumb): string => $crumb->text()))->toBe(['Articles', 'Create'])
            ->and($page->filter(SupportSelectors::LINKED_BREADCRUMB)->each(static fn ($crumb): ?string => $crumb->attr('href')))->toBe(['http://localhost/admin/articles'])
            ->and($page->filter(SupportSelectors::CURRENT_BREADCRUMB)->text())->toBe('Create');
    });

    it('trails a titled record page with its listing and its record, costing no statement beyond reading the record', function (): void {
        $this->seed($article = new Article('Draft'));

        $page = $this->visit("/admin/articles/{$article->id}/edit");

        expect($page->filter(SupportSelectors::BREADCRUMB)->each(static fn ($crumb): string => $crumb->text()))->toBe(['Articles', 'Draft', 'Edit'])
            ->and($this->statementsRun())->toHaveCount(1);
    });

    it('answers the crumb a page declares, and leaves the trail out where the page asks to', function (): void {
        $page = $this->visit('/resource-routes/articles-archive/digest');

        expect($page->filter(Selectors::PAGE_HEADING)->text())->toBe('Monthly digest')
            ->and($page->filter(SupportSelectors::BREADCRUMB)->each(static fn ($crumb): string => $crumb->text()))->toBe(['Digest']);
    });

    it('crumbs a page declaring none with its title, markup and all', function (): void {
        $page = $this->visit('/resource-routes/articles-archive/notice');

        expect($page->filter(SupportSelectors::BREADCRUMB)->each(static fn ($crumb): string => $crumb->text()))->toBe(['Articles', 'Archive notice'])
            ->and($page->filter(SupportSelectors::CURRENT_BREADCRUMB . ' em')->text())->toBe('notice');
    });
});

describe('host defaults', function (): void {
    it("links a create action to the resource's create page under the resource's label, and leaves one acting on another record class to mount", function (): void {
        $header = $this->visit('/resource-routes/scoped-articles')->filter(Selectors::HEADER_ACTIONS);

        expect($header->filter('a[href]')->each(static fn ($link): array => [$link->text(), $link->attr('href')]))
            ->toBe([['New shortlisted article', 'http://localhost/resource-routes/scoped-articles/create']])
            ->and($header->filter(ActionsSelectors::trigger('createHooked'))->text())->toBe('New hooked article');
    });

    it("links a row's edit action to its record's edit page, and leaves a view action with no view page, or an edit action with no record, to mount", function (): void {
        $this->seed($alpha = new Article('Alpha'));

        $page = $this->visit('/resource-routes/scoped-articles');
        $row = $page->filter(TablesSelectors::row((string) $alpha->id));

        expect($row->filter(TablesSelectors::RECORD_ACTIONS . ' a[href]')->each(static fn ($link): array => [$link->text(), $link->attr('href')]))
            ->toBe([['pencil-square-20-solidEdit', "http://localhost/resource-routes/scoped-articles/{$alpha->id}/edit"]])
            ->and($row->filter(ActionsSelectors::trigger('view'))->count())->toBe(1)
            ->and($page->filter(Selectors::HEADER_ACTIONS . ' ' . ActionsSelectors::trigger('edit'))->count())->toBe(1);
    });

    it("leaves an edit action on the edit page unlinked, since it would link the page to itself", function (): void {
        $this->seed($alpha = new Article('Alpha'));

        $header = $this->visit("/resource-routes/scoped-articles/{$alpha->id}/edit")->filter(Selectors::HEADER_ACTIONS);

        expect($header->filter(ActionsSelectors::trigger('edit'))->count())->toBe(1)
            ->and($header->filter('a[href]')->count())->toBe(0);
    });
});

describe('mounted action record', function (): void {
    it("mounts a header action on the record its frame's key names, which a non-scalar key leaves unmounted", function (): void {
        $this->seed($alpha = new Article('Alpha'));

        $tester = $this->createLiveComponent(ListScopedArticles::class, ['panelId' => 'resource-routes'])
            ->mountAction(['name' => 'delete', 'context' => ['recordKey' => [$alpha->id]]])
            ->assertActionNotMounted('delete')
            ->mountAction($frame = ['name' => 'delete', 'context' => ['recordKey' => (string) $alpha->id]])
            ->assertActionMounted($frame);

        expect($tester->component()->getMountedAction())->toBeInstanceOf(DeleteAction::class)
            ->and($tester->component()->getMountedAction()?->getRecord())->toBeRecord(id: $alpha->id, title: 'Alpha')
            ->and($tester->render()->crawler()->filter(SupportSelectors::MODAL_HEADING)->text())->toBe('Delete shortlisted article');
    });

    it("refuses as missing a key naming no row of the resource's own query", function (Closure $key): void {
        $this->seed($alpha = new Article('Alpha'), $beta = new Article('Beta'));
        $tester = $this->createLiveComponent(ListScopedArticles::class, ['panelId' => 'resource-routes']);

        expect(fn (): mixed => $tester->mountAction(['name' => 'delete', 'context' => ['recordKey' => $key($beta)]]))
            ->toThrow(NotFoundHttpException::class)
            ->and($this->statementsRun())->toHaveCount(1);
    })->with([
        'a row outside its scope' => [static fn (Article $beta): string => (string) $beta->id],
        'no row at all' => [static fn (): string => '999'],
    ]);

    it('refuses as missing a key malformed for the record class, without running a statement', function (): void {
        $this->seed(new Article('Alpha'));
        $tester = $this->createLiveComponent(ListScopedArticles::class, ['panelId' => 'resource-routes']);

        expect(fn (): mixed => $tester->mountAction(['name' => 'delete', 'context' => ['recordKey' => 'abc']]))
            ->toThrow(NotFoundHttpException::class)
            ->and($this->statementsRun())->toBe([]);
    });
});
