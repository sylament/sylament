<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListTabbedArticles;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;
use Symfony\Component\DomCrawler\Crawler;

it('renders a strip of the tabs the page declares above its table, in order, each labelled by its own label or one generated from its key', function (): void {
    $this->seed(new Article('Apple'));

    $page = $this->visit('/list-pages/shelf/articles');
    $strip = $page->filter(Selectors::PAGE . ' ' . SchemasSelectors::schemaKeyed('tabs') . ' [id="tabs.resourceTabs"]' . SchemasSelectors::TABS . ':not(' . SupportSelectors::CONTAINED . ')' . SchemasSelectors::UNCONTROLLED);

    expect($strip->filter(SchemasSelectors::TAB)->each(static fn (Crawler $tab): string => $tab->text()))->toBe(['All', 'Drafts', 'Loose drafts', 'Needs review', 'To check'])
        ->and($strip->filter(SchemasSelectors::TAB . '[data-model="activeTab"][data-action~="live#update"]')->each(static fn (Crawler $tab): ?string => $tab->attr('data-value')))
        ->toBe(['all', 'drafts', 'loose', 'needs_review', 'to-check'])
        ->and($page->filterXPath('//*[@id="tabs.resourceTabs"]/following::table'))->toHaveCount(1);
});

it('opens on its first tab, or the declared tab the query string names, scoped by it, and on its first again for a blank or undeclared one', function (string $query, string $key, string $label, array $titles): void {
    $this->seed(new Article('Apple'), new Article('Draft Bean'), new Article('Draft Corn'));

    $page = $this->visit('/list-pages/shelf/articles' . $query);

    expect($page->filter(SchemasSelectors::TAB . '[aria-selected="true"]')->text())->toBe($label)
        ->and($page->filter(TablesSelectors::ROW . ' ' . TablesSelectors::TEXT_COLUMN)->each(static fn (Crawler $cell): string => $cell->text()))->toBe($titles)
        ->and($this::renderedLiveProps($page)['activeTab'])->toBe($key);
})->with([
    'no tab named' => ['', 'all', 'All', ['Apple', 'Draft Bean']],
    'a declared tab' => ['?tab=drafts', 'drafts', 'Drafts', ['Draft Bean', 'Draft Corn']],
    'a blank tab' => ['?tab=', 'all', 'All', ['Apple', 'Draft Bean']],
    'a tab it no longer declares' => ['?tab=gone', 'all', 'All', ['Apple', 'Draft Bean']],
]);

it('builds its tabs from the properties the page opened with, and renders the strip on its first tab once a later request begins declaring tabs', function (): void {
    $this->seed(new Article('Apple'));

    $page = $this->visit('/list-pages/shelf/articles?tabless=1');

    expect($page->filter(TablesSelectors::ROW))->toHaveCount(1)
        ->and($page->filter(SchemasSelectors::TABS))->toHaveCount(0);

    $tester = $this->createLiveComponent(ListTabbedArticles::class, ['panelId' => 'list-pages', 'tabless' => true])->set('tabless', false);

    expect($tester->render()->crawler()->filter(SchemasSelectors::TAB . '[aria-selected="true"]')->text())->toBe('All');
});

it('lists every record once a later request stops declaring the tab it had active', function (): void {
    $this->seed($apple = new Article('Apple'), $bean = new Article('Draft Bean'));

    $this->createLiveComponent(ListTabbedArticles::class, ['panelId' => 'list-pages', 'activeTab' => 'drafts'])
        ->assertCanSeeTableRecords([$bean])
        ->assertCanNotSeeTableRecords([$apple])
        ->set('tabless', true)
        ->assertCanSeeTableRecords([$apple, $bean], inOrder: true);
});

it('switches tab back on the first page, showing the rows the new tab scopes, and heals a blanked tab to the first in the same response', function (): void {
    $this->seed($apple = new Article('Apple'), $bean = new Article('Draft Bean'), $corn = new Article('Draft Corn'));

    $tester = $this->createLiveComponent(ListTabbedArticles::class, ['panelId' => 'list-pages'])
        ->call('gotoTablePage', ['page' => 2])
        ->assertCanSeeTableRecords([$corn])
        ->set('activeTab', 'drafts')
        ->assertCanSeeTableRecords([$bean, $corn], inOrder: true)
        ->assertCanNotSeeTableRecords([$apple]);

    expect($this::renderedLiveProps($tester->render()->crawler()))->toMatchArray(['activeTab' => 'drafts', 'tablePage' => 1]);

    $tester->set('activeTab', null);

    expect($this::renderedLiveProps($tester->render()->crawler())['activeTab'])->toBe('all')
        ->and($tester->render()->crawler()->filter(SchemasSelectors::TAB . '[aria-selected="true"]')->text())->toBe('All');
});

it("keeps a record outside the active tab's scope out of a row action's reach, unless the tab leaves its scope out of the lookup", function (): void {
    $this->seed($apple = new Article('Apple'), new Article('Draft Bean'));

    $this->createLiveComponent(ListTabbedArticles::class, ['panelId' => 'list-pages', 'activeTab' => 'drafts'])
        ->mountAction(TestAction::make('delete')->table($apple))
        ->assertActionNotMounted()
        ->set('activeTab', 'loose')
        ->callAction(TestAction::make('delete')->table($apple));

    expect(array_map(static fn (Article $article): string => $article->title, $this->entityManager()->getRepository(Article::class)->findAll()))->toBe(['Draft Bean']);
});
