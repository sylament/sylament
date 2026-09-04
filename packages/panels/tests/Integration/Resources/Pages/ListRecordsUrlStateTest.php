<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Project;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Task;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditTwoRelationProject;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/**
 * Twelve articles against the resource's default of ten per page, so a seeded
 * `?page=2` has exactly two rows to land on.
 *
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function urlStateArticlesClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $articles = [];

    foreach (range(1, 12) as $index) {
        $slug = sprintf('article-%02d', $index);

        $articles[] = new Article()->setTitle(sprintf('Article %02d', $index))->setSlug($slug);
    }

    EntityManagerFactory::seed($entityManager, ...$articles);

    return [$client, $entityManager];
}

/**
 * @return list<string>
 */
function urlStateRowTitles(Crawler $crawler): array
{
    return $crawler->filter('tbody .sy-ta-row')->each(fn (Crawler $row): string => mb_trim($row->filter('td')->first()->text()));
}

/**
 * Keyed by wire name and sorted, because the mapping's order follows the trait
 * composition rather than anything a reader would predict.
 *
 * @return array<string, array{name: string}>
 */
function urlStateQueryMapping(Crawler $crawler, string $selector): array
{
    $value = $crawler->filter($selector)->attr('data-live-query-mapping-value');

    if ($value === null) {
        return [];
    }

    $mapping = json_decode($value, associative: true, flags: JSON_THROW_ON_ERROR);

    ksort($mapping);

    return $mapping;
}

it("maps every browsing prop of a routed list onto Filament's query-string names", function (): void {
    [$client] = urlStateArticlesClient();

    $crawler = $client->request('GET', '/admin/articles');

    // Six, and the count is the assertion: a browsing prop the page does not
    // map is absent rather than defaulted, which is what keeps the per-column
    // search bag off the URL of every routed list.
    expect(urlStateQueryMapping($crawler, '.sy-pa-resource-list-records-page'))->toBe([
        'activeTab' => ['name' => 'tab'],
        'tableFilters' => ['name' => 'filters'],
        'tableGrouping' => ['name' => 'grouping'],
        'tablePage' => ['name' => 'page'],
        'tableSearch' => ['name' => 'search'],
        'tableSort' => ['name' => 'sort'],
    ]);
});

it('seeds the sort from the query string', function (): void {
    [$client] = urlStateArticlesClient();

    $crawler = $client->request('GET', '/admin/articles?sort=title:desc');

    expect(urlStateRowTitles($crawler)[0])->toBe('Article 12');
});

it('seeds the search from the query string', function (): void {
    [$client] = urlStateArticlesClient();

    $crawler = $client->request('GET', '/admin/articles?search=Article 07');

    expect($crawler->filter('tbody .sy-ta-row')->count())->toBe(1)
        ->and(urlStateRowTitles($crawler)[0])->toBe('Article 07');
});

it('seeds the page from the query string', function (): void {
    [$client] = urlStateArticlesClient();

    $crawler = $client->request('GET', '/admin/articles?page=2');

    expect($crawler->filter('tbody .sy-ta-row')->count())->toBe(2)
        ->and(urlStateRowTitles($crawler))->toBe(['Article 11', 'Article 12']);
});

it('carries the sort, the search and the page of one deep link together', function (): void {
    [$client] = urlStateArticlesClient();

    $crawler = $client->request('GET', '/admin/articles?sort=title:desc&search=Article&page=2');

    // All twelve match the search, so the second page of a descending sort is
    // the two lowest-numbered ones — a page seeded without the sort would show
    // the two highest instead.
    expect($crawler->filter('tbody .sy-ta-row')->count())->toBe(2)
        ->and(urlStateRowTitles($crawler))->toBe(['Article 02', 'Article 01']);
});

it('floors a page the query string sends below one', function (): void {
    [$client] = urlStateArticlesClient();

    $crawler = $client->request('GET', '/admin/articles?page=0');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('tbody .sy-ta-row')->count())->toBe(10);
});

it('serves a list whose seeded sort names a column it does not offer', function (): void {
    [$client] = urlStateArticlesClient();

    $crawler = $client->request('GET', '/admin/articles?sort=nonsense:desc');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('tbody .sy-ta-row')->count())->toBe(10);
});

it('prefixes the page parameter of a manage page with its own identifier', function (): void {
    $client = $this->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = $this->getContainer()->get(EntityManagerInterface::class);
    $project = new Project()->setName('Apollo');

    EntityManagerFactory::seed(
        $entityManager,
        $project,
        new Task()->setTitle('Alpha')->setPosition(1)->setProject($project),
        new Task()->setTitle('Beta')->setPosition(2)->setProject($project),
    );

    $crawler = $client->request('GET', sprintf('/mrr/projects/%s/tasks', $project->getId()));

    // The identifier prefixes the page parameter and nothing else: the other
    // five names are frozen at the mapping, exactly as Filament freezes them at
    // the attribute.
    expect(urlStateQueryMapping($crawler, '.sy-pa-resource-manage-related-records-page'))->toBe([
        'activeTab' => ['name' => 'tab'],
        'tableFilters' => ['name' => 'filters'],
        'tableGrouping' => ['name' => 'grouping'],
        'tablePage' => ['name' => 'manageMrrProjectTasksPage'],
        'tableSearch' => ['name' => 'search'],
        'tableSort' => ['name' => 'sort'],
    ]);
});

it('binds the relation of an edit page and leaves its managers unbound', function (): void {
    $client = $this->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = $this->getContainer()->get(EntityManagerInterface::class);
    $project = new Project()->setName('Apollo');

    EntityManagerFactory::seed(
        $entityManager,
        $project,
        new Task()->setTitle('Alpha')->setPosition(1)->setProject($project),
    );

    $html = (string) $this->createLiveComponent(
        EditTwoRelationProject::class,
        ['record' => (string) $project->getId()],
        client: $client,
    )->render();

    $crawler = new Crawler($html);

    expect(urlStateQueryMapping($crawler, '.sy-pa-resource-edit-record-page')['activeRelationManager'] ?? null)->toBe(['name' => 'relation'])
        // The managers are nested live roots on the page's own URL, so binding
        // any state of theirs would rewrite that URL from the child.
        ->and($crawler->filter('.sy-pa-relation-manager')->count())->toBeGreaterThan(0)
        ->and($crawler->filter('.sy-pa-relation-manager[data-live-query-mapping-value]')->count())->toBe(0);
});

it('seeds the grouping of a routed list from the query string', function (): void {
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    EntityManagerFactory::seed(
        $entityManager,
        new Article()->setTitle('Published one')->setSlug('published-one')->setStatus('published'),
        new Article()->setTitle('Draft one')->setSlug('draft-one')->setStatus('draft'),
        new Article()->setTitle('Published two')->setSlug('published-two')->setStatus('published'),
    );

    $crawler = $client->request('GET', '/probe/grouping-articles?grouping=status:desc');

    // The grouping orders the rows before any sort does, so a descending group
    // by status is what puts the published pair ahead of the draft.
    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and(urlStateRowTitles($crawler))->toBe(['Published one', 'Published two', 'Draft one']);
});

it('drops a grouping the routed list does not offer', function (): void {
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    EntityManagerFactory::seed($entityManager, new Article()->setTitle('Only')->setSlug('only'));

    $crawler = $client->request('GET', '/probe/grouping-articles?grouping=nonsense');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and(urlStateRowTitles($crawler))->toBe(['Only']);
});
