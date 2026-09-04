<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListArticles;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The column manager and the per-column terms on a routed list page, where the
 * table is built by a resource rather than by a fixture host: the manager reads
 * and writes the panel session across two visits, and neither half of the epic
 * reaches the query string the page does bind.
 */

/**
 * Twelve articles against the resource's default of ten per page — the seed
 * `ListRecordsUrlStateTest` uses, duplicated because panels suites carry their
 * own rig rather than reaching into a sibling file's.
 *
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function columnManagerArticlesClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $articles = [];

    foreach (range(1, 12) as $index) {
        $articles[] = new Article()->setTitle(sprintf('Article %02d', $index))->setSlug(sprintf('article-%02d', $index));
    }

    EntityManagerFactory::seed($entityManager, ...$articles);

    return [$client, $entityManager];
}

/**
 * @return list<string>
 */
function columnManagerHeaderLabels(Crawler $crawler): array
{
    return $crawler->filter('thead .sy-ta-header-cell')->each(fn (Crawler $cell): string => mb_trim($cell->text()));
}

/**
 * @return list<string>
 */
function columnManagerRowTitles(Crawler $crawler): array
{
    return $crawler->filter('tbody .sy-ta-row')->each(fn (Crawler $row): string => mb_trim($row->filter('td')->first()->text()));
}

/**
 * @return array<string, array{name: string}>
 */
function columnManagerQueryMapping(Crawler $crawler, string $selector): array
{
    $value = $crawler->filter($selector)->attr('data-live-query-mapping-value');

    if ($value === null) {
        return [];
    }

    $mapping = json_decode($value, associative: true, flags: JSON_THROW_ON_ERROR);

    ksort($mapping);

    return $mapping;
}

/**
 * One column's choice flipped inside the state the page itself published, so an
 * applied state is never a literal that could drift from the stored shape.
 *
 * @param list<array{type: string, name: string, isToggled: bool, isToggledHiddenByDefault: bool|null}> $state
 *
 * @return list<array{type: string, name: string, isToggled: bool, isToggledHiddenByDefault: bool|null}>
 */
function columnManagerStateToggling(array $state, string $name, bool $isToggled): array
{
    return array_map(static function (array $item) use ($name, $isToggled): array {
        if ($item['name'] === $name) {
            $item['isToggled'] = $isToggled;
        }

        return $item;
    }, $state);
}

function columnManagerSessionKey(): string
{
    return sprintf('sylament.tables.%s_columns', md5(ListArticles::class));
}

it('offers the manager over a resource table and leaves a toggled-hidden column out of the header', function (): void {
    [$client] = columnManagerArticlesClient();

    $crawler = $client->request('GET', '/admin/articles');

    // The resource declares the manager nowhere: one toggleable column is what
    // puts the trigger in the toolbar, and the column it hides is offered by
    // the panel while being absent from the table.
    expect($crawler->filter('.sy-ta-header-toolbar-end button.sy-icon-btn[aria-label="Column manager"]')->count())->toBe(1)
        ->and(columnManagerHeaderLabels($crawler))->toBe(['Title', 'Status'])
        ->and($crawler->filter('.sy-ta-col-manager-item-label')->each(fn (Crawler $label): string => mb_trim($label->text())))
        ->toBe(['Title', 'Slug', 'Status']);
});

it('renders a column the manager applied', function (): void {
    [$client] = columnManagerArticlesClient();

    $client->request('GET', '/admin/articles');

    $component = $this->createLiveComponent(ListArticles::class, client: $client);
    $state = columnManagerStateToggling($component->component()->tableColumns, 'slug', true);

    $crawler = $component->call('applyTableColumnManager', ['state' => $state])->render()->crawler();

    expect(columnManagerHeaderLabels($crawler))->toBe(['Title', 'Slug', 'Status'])
        ->and($crawler->filter('tbody .sy-ta-row')->first()->filter('td')->eq(1)->text())->toBe('article-01');
});

it('remembers the applied columns for the next visit to the page', function (): void {
    [$client] = columnManagerArticlesClient();

    $client->request('GET', '/admin/articles');

    $component = $this->createLiveComponent(ListArticles::class, client: $client);
    $state = columnManagerStateToggling($component->component()->tableColumns, 'slug', true);

    $component->call('applyTableColumnManager', ['state' => $state]);

    $crawler = $client->request('GET', '/admin/articles');
    $stored = $client->getRequest()->getSession()->get(columnManagerSessionKey());

    // The key is the page class alone, so the plain GET that follows the apply
    // reads back a choice no query string carried.
    expect(columnManagerHeaderLabels($crawler))->toBe(['Title', 'Slug', 'Status'])
        ->and(array_column(is_array($stored) ? $stored : [], 'isToggled', 'name'))
        ->toBe(['title' => true, 'slug' => true, 'status' => true]);
});

it('leaves the per-column terms off the query string a routed list binds', function (): void {
    [$client] = columnManagerArticlesClient();

    $crawler = $client->request('GET', '/admin/articles?columnSearches[title]=Article 07');

    // Every key but this one: the page maps no `columnSearches`, so the modifier
    // hands the prop back unbound and a deep link naming it seeds nothing at all.
    expect(columnManagerQueryMapping($crawler, '.sy-pa-resource-list-records-page'))->toBe([
        'activeTab' => ['name' => 'tab'],
        'tableFilters' => ['name' => 'filters'],
        'tableGrouping' => ['name' => 'grouping'],
        'tablePage' => ['name' => 'page'],
        'tableSearch' => ['name' => 'search'],
        'tableSort' => ['name' => 'sort'],
    ])
        ->and($crawler->filter('tbody .sy-ta-row')->count())->toBe(10);
});

it('narrows a resource list by one column term and chips it', function (): void {
    [$client] = columnManagerArticlesClient();

    $client->request('GET', '/admin/articles');

    $crawler = $this->createLiveComponent(ListArticles::class, client: $client)
        ->set('tableColumnSearches.title', 'Article 1')
        ->render()
        ->crawler();

    // Four of twelve, and the reason is the table's split flag rather than a
    // substring: the term becomes `Article` and `1`, both of which every one of
    // these four titles carries — `Article 01` among them, which a contiguous
    // `Article 1` would have missed.
    expect(columnManagerRowTitles($crawler))->toBe(['Article 01', 'Article 10', 'Article 11', 'Article 12'])
        ->and($crawler->filter('.sy-ta-filter-indicator')->each(fn (Crawler $chip): string => mb_trim($chip->text())))
        ->toBe(['Title: Article 1']);
});
