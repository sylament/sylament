<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Actions\Selectors as ActionsSelectors;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListArticles;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListModalArticles;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListRestampedArticles;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;
use Symfony\Component\DomCrawler\Crawler;

describe('the page', function (): void {
    it("renders the resource's table with its records inside the page chrome, with no tab strip and no header action it does not declare", function (): void {
        $this->seed(new Article('Apple'), new Article('Draft Bean'));

        $page = $this->visit('/admin/articles');

        expect($page->filter(Selectors::PAGE . ' ' . TablesSelectors::ROW . ' ' . TablesSelectors::TEXT_COLUMN)->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['Apple', 'Draft Bean'])
            ->and($page->filter(Selectors::PAGE . ' ' . Selectors::PAGE_HEADING)->text())->toBe('Articles')
            ->and($page->filter(SchemasSelectors::TABS))->toHaveCount(0)
            ->and($page->filter(Selectors::HEADER_ACTIONS))->toHaveCount(0);
    });

    it('titles, heads and crumbs itself with its resource\'s plural label and "List", or the statics it declares, and marks its root with the resource\'s slug', function (string $uri, string $title, string $crumb, string $slugClass): void {
        $page = $this->visit($uri);

        expect($page->filter(Selectors::DOCUMENT_TITLE)->text())->toStartWith($title)
            ->and($page->filter(Selectors::PAGE_HEADING)->text())->toBe($title)
            ->and($page->filter(SupportSelectors::CURRENT_BREADCRUMB)->text())->toBe($crumb)
            ->and($page->filter(Selectors::PAGE . ".sy-pa-resource-list-records-page.sy-pa-resource-{$slugClass}"))->toHaveCount(1);
    })->with([
        'by default' => ['/admin/articles', 'Articles', 'List', 'articles'],
        'declaring both, under a slug with a slash' => ['/list-pages/shelf/articles', 'Article shelf', 'Shelf', 'shelf-articles'],
    ]);

    it("shows the empty state under the resource's plural label", function (): void {
        expect($this->visit('/resource-routes/scoped-articles'))->toContainElement(TablesSelectors::EMPTY_STATE . ' h2, ' . TablesSelectors::EMPTY_STATE . ' h3', 'No shortlisted articles');
    });
});

describe('the table', function (): void {
    it("lists the rows of the query the resource's own table() puts in place of the stamped one", function (): void {
        $this->seed(new Article('Apple'), new Article('Withdrawn Fig'), new Article('Draft Bean'));

        expect($this->visit('/list-pages/restamped-articles')->filter(TablesSelectors::ROW . ' ' . TablesSelectors::TEXT_COLUMN)->each(static fn (Crawler $cell): string => $cell->text()))
            ->toBe(['Apple', 'Draft Bean']);
    });

    it("titles a row action with the record title the resource's own getRecordTitle() answers", function (): void {
        $this->seed($apple = new Article('Apple'));

        $modal = $this->createLiveComponent(ListRestampedArticles::class, ['panelId' => 'list-pages'])
            ->mountAction(TestAction::make('delete')->table($apple))
            ->render()
            ->crawler();

        expect($modal->filter(SupportSelectors::MODAL_HEADING)->text())->toBe('Delete "Apple"');
    });

    it("titles a header action with the resource's record title for the record its frame names, as a row action is titled", function (): void {
        $this->seed($draft = new Article('Draft'));

        $tester = $this->createLiveComponent(ListModalArticles::class, ['panelId' => 'list-pages']);

        expect($tester->mountAction(['name' => 'delete', 'context' => ['recordKey' => (string) $draft->id]])->render()->crawler()->filter(SupportSelectors::MODAL_HEADING)->text())->toBe('Delete Draft')
            ->and($tester->unmountAction()->mountAction(TestAction::make('delete')->table($draft))->render()->crawler()->filter(SupportSelectors::MODAL_HEADING)->text())->toBe('Delete Draft');
    });
});

describe('the row click', function (): void {
    it("links each row to the record's edit page, by its route key, when neither the table nor a row action gives a URL", function (string $uri, Closure $record, string $href): void {
        $this->seed($record());

        expect($this->visit($uri)->filter(TablesSelectors::ROW . ' ' . TablesSelectors::LINKED_CELL_WRAPPER)->attr('href'))->toBe($href);
    })->with([
        'a generated id' => ['/admin/articles', static fn (): Article => new Article('Apple'), 'http://localhost/admin/articles/1/edit'],
        'scalar fields' => ['/admin/order-lines', static fn (): OrderLine => new OrderLine(order: 7, line: 2, note: 'First'), 'http://localhost/admin/order-lines/7~2/edit'],
        'an association plus a scalar' => ['/admin/invoice-lines', static fn (): InvoiceLine => new InvoiceLine(new Invoice(7), line: 2, note: 'First'), 'http://localhost/admin/invoice-lines/7~2/edit'],
    ]);

    it("links each row to the URL the resource's own table() sets, over the seeded one", function (): void {
        $this->seed($apple = new Article('Apple'));

        expect($this->visit('/list-pages/restamped-articles')->filter(TablesSelectors::ROW . ' ' . TablesSelectors::LINKED_CELL_WRAPPER)->attr('href'))->toBe("/list-pages/clippings/{$apple->id}");
    });

    it('seeds no row URL over one a table configuration sets before the page seeds its own', function (): void {
        $this->seed($apple = new Article('Apple'));

        Table::configureUsing(static fn (Table $table): Table => $table->recordUrl(static fn (Article $record): string => "/configured/{$record->id}"));

        expect($this->createLiveComponent(ListArticles::class)->render()->crawler()->filter(TablesSelectors::ROW . ' ' . TablesSelectors::LINKED_CELL_WRAPPER)->attr('href'))->toBe("/configured/{$apple->id}");
    });

    it('links each row to the URL of its first visible view or edit action, skipping a hidden one', function (): void {
        $this->seed($apple = new Article('Apple'));

        expect($this->visit('/list-pages/shelf/articles')->filter(TablesSelectors::ROW . ' ' . TablesSelectors::LINKED_CELL_WRAPPER)->attr('href'))->toBe("/list-pages/edit-elsewhere/{$apple->id}");
    });

    it('makes each row a button mounting its first visible view or edit action carrying no URL, skipping a hidden one', function (): void {
        $this->seed($apple = new Article('Apple'), $sealed = new Article('Sealed'));

        $page = $this->visit('/list-pages/modal-articles');
        $clickTarget = static fn (Article $article): ?string => $page->filter(TablesSelectors::row((string) $article->id) . ' ' . TablesSelectors::BUTTON_CELL_WRAPPER . ActionsSelectors::LIVE_CLICK)->attr('data-live-name-param');

        expect($clickTarget($apple))->toBe('view')
            ->and($clickTarget($sealed))->toBe('edit')
            ->and($page->filter(TablesSelectors::ROW . ' ' . TablesSelectors::LINKED_CELL_WRAPPER))->toHaveCount(0);
    });
});

describe('the query string', function (): void {
    it("seeds the table's search, filters and page from Filament's names for them", function (string $query, array $titles): void {
        $this->seed(new Article('Apple'), new Article('Draft Bean'), new Article('Draft Corn'));

        expect($this->visit('/list-pages/shelf/articles' . $query)->filter(TablesSelectors::ROW . ' ' . TablesSelectors::TEXT_COLUMN)->each(static fn (Crawler $cell): string => $cell->text()))
            ->toBe($titles);
    })->with([
        'a search' => ['?search=Corn', ['Draft Corn']],
        'a filter' => ['?filters[drafts][isActive]=1', ['Draft Bean', 'Draft Corn']],
        'a page' => ['?page=2', ['Draft Corn']],
        'none' => ['', ['Apple', 'Draft Bean']],
    ]);

    it("binds the page number under the table's query-string identifier when the page names one", function (string $query, array $titles): void {
        $this->seed(new Article('Apple'), new Article('Draft Bean'), new Article('Draft Corn'));

        expect($this->visit('/list-pages/modal-articles' . $query)->filter(TablesSelectors::ROW . ' ' . TablesSelectors::TEXT_COLUMN)->each(static fn (Crawler $cell): string => $cell->text()))
            ->toBe($titles);
    })->with([
        'the prefixed name' => ['?articlesPage=2', ['Draft Corn']],
        'the bare name' => ['?page=2', ['Apple', 'Draft Bean']],
    ]);

    it('opens sorted by a sortable column the query string names in a known direction, and unsorted for an unknown direction', function (string $query, string $state, array $titles): void {
        $this->seed(new Article('Apple'), new Article('Draft Bean'), new Article('Draft Corn'));

        $page = $this->visit('/list-pages/shelf/articles' . $query);

        expect($page->filter(TablesSelectors::sortState('title', $state)))->toHaveCount(1)
            ->and($page->filter(TablesSelectors::ROW . ' ' . TablesSelectors::TEXT_COLUMN)->each(static fn (Crawler $cell): string => $cell->text()))->toBe($titles);
    })->with([
        'a known direction' => ['?sort=title:desc', 'descending', ['Draft Corn', 'Draft Bean']],
        'an unknown direction' => ['?sort=title:sideways', 'none', ['Apple', 'Draft Bean']],
    ]);

    it('opens grouped by a group the query string names, and ungrouped for a group the table does not offer or a blank one', function (string $query, int $headers, ?string $grouping): void {
        $this->seed(new Article('Apple'), new Article('Draft Bean'));

        $page = $this->visit('/list-pages/shelf/articles' . $query);

        expect($page->filter(TablesSelectors::GROUP_HEADER_ROW))->toHaveCount($headers)
            ->and($page->filter(TablesSelectors::ROW))->toHaveCount(2)
            ->and($this::renderedLiveProps($page)['tableGrouping'])->toBe($grouping);
    })->with([
        'an offered group' => ['?grouping=title', 2, 'title'],
        'a group it does not offer' => ['?grouping=nope', 0, null],
        'a blank group' => ['?grouping=', 0, null],
    ]);
});

describe('the modals', function (): void {
    it('fills a create, edit or view modal with the resource form, and gives a delete modal none', function (Closure $action, ?string $value, bool $disabled): void {
        $this->seed($draft = new Article('Draft'));

        $page = $this->createLiveComponent(ListModalArticles::class, ['panelId' => 'list-pages'])
            ->mountAction($action($draft))
            ->render()
            ->crawler();
        $field = $page->filter(SupportSelectors::OPEN_MODAL . ' ' . Selectors::field('title'));

        expect($page->filter(Selectors::PAGE . ' ' . ActionsSelectors::MOUNTED_MODAL . ActionsSelectors::OPEN))->toHaveCount(1)
            ->and($field)->toHaveCount($value === null ? 0 : 1);

        if ($value !== null) {
            expect($field->attr('value'))->toBe($value)
                ->and($field->attr('disabled'))->toBe($disabled ? '' : null);
        }
    })->with([
        'a header create' => [static fn (): string => 'create', '', false],
        'a row edit' => [static fn (Article $draft): TestAction => TestAction::make('edit')->table($draft), 'Draft', false],
        'a row view' => [static fn (Article $draft): TestAction => TestAction::make('view')->table($draft), 'Draft', true],
        'a row delete' => [static fn (Article $draft): TestAction => TestAction::make('delete')->table($draft), null, false],
    ]);

    it("renders the create action it declares in its header, opening a modal under the resource's record label", function (): void {
        $tester = $this->createLiveComponent(ListModalArticles::class, ['panelId' => 'list-pages']);

        expect($tester->render()->crawler())->toContainElement(Selectors::HEADER_ACTIONS . ' ' . ActionsSelectors::trigger('create'), 'New article');

        $tester->mountAction('create')->assertActionMounted('create');

        expect($tester->render()->crawler()->filter(SupportSelectors::MODAL_HEADING)->text())->toBe('Create Article');
    });

    it("keeps the resource form's own single column in a modal", function (): void {
        $modal = $this->createLiveComponent(ListModalArticles::class, ['panelId' => 'list-pages'])
            ->mountAction('create')
            ->render()
            ->crawler()
            ->filter(SupportSelectors::OPEN_MODAL);

        expect($modal->filter(SchemasSelectors::schemaKeyed('mountedActionSchema0') . SupportSelectors::ONE_COLUMN))->toHaveCount(1);
    });
});
