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
 * Kernel reboot is disabled for every flow here: the fixture database is an
 * in-memory SQLite built by a container service, so a reboot between two
 * requests would hand the second one a freshly-migrated, empty database — the
 * shape `PrebuiltDeleteActionTest` established in the tables suite.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function seededArticlesClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    EntityManagerFactory::seed(
        $entityManager,
        new Article()->setTitle('Alpha')->setSlug('alpha'),
        new Article()->setTitle('Beta')->setSlug('beta')->setStatus('published'),
    );

    return [$client, $entityManager];
}

function articleKey(EntityManagerInterface $entityManager, string $slug): string
{
    $article = $entityManager->getRepository(Article::class)->findOneBy(['slug' => $slug]);

    expect($article)->toBeInstanceOf(Article::class);

    return (string) $article?->getId();
}

it('serves the list page with the resource table', function (): void {
    [$client] = seededArticlesClient();

    $crawler = $client->request('GET', '/admin/articles');
    $html = (string) $client->getResponse()->getContent();

    $rows = $crawler->filter('tbody .sy-ta-row');
    $rowText = implode(' ', $rows->each(fn (Crawler $row): string => $row->text()));

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('h1.sy-pa-header-heading')->text())->toBe('Articles')
        // The rhythm wrapper is what spaces the header off the content, so the
        // whole chain is pinned as direct children rather than as descendants.
        ->and($crawler->filter('.sy-pa-resource-list-records-page > .sy-pa-page-header-main-ctn > header.sy-pa-header')->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-resource-list-records-page > .sy-pa-page-header-main-ctn > .sy-pa-page-main > .sy-pa-page-content > .sy-ta')->count())->toBe(1)
        ->and($rows->count())->toBe(2)
        ->and($rowText)->toContain('Alpha')
        ->and($rowText)->toContain('published')
        ->and($html)->toContain('<title>Articles — Test Admin</title>');
});

it('renders the header actions the page declares', function (): void {
    [$client] = seededArticlesClient();

    $crawler = $client->request('GET', '/admin/articles');

    $purge = $crawler->filter('.sy-pa-header-actions button');
    $create = $crawler->filter('.sy-pa-header-actions a');

    // The two arms a header action can render as, and each one's whole binding:
    // the mounting action posts its own name back through the live controller,
    // the url-mode one is an anchor and carries no binding at all.
    expect($purge->text())->toContain('Purge')
        ->and($purge->attr('data-action'))->toBe('live#action')
        ->and($purge->attr('data-live-action-param'))->toBe('mountAction')
        ->and($purge->attr('data-live-name-param'))->toBe('purge')
        ->and($create->text())->toContain('Create')
        ->and($create->attr('href'))->toEndWith('/admin/articles/create')
        ->and($create->attr('data-live-action-param'))->toBeNull();
});

it('links each row to its own edit page and leaves the other row actions mounting', function (): void {
    [$client, $entityManager] = seededArticlesClient();

    $crawler = $client->request('GET', '/admin/articles');

    $rows = $crawler->filter('tbody .sy-ta-row');

    expect($rows->count())->toBe(2);

    foreach ($rows as $row) {
        $rowCrawler = new Crawler($row);
        // Read the key back off the row's own title rather than assuming a row order.
        $key = articleKey($entityManager, mb_strtolower($rowCrawler->filter('.sy-ta-cell')->first()->text()));
        $actions = $rowCrawler->filter('.sy-ta-actions');
        $edit = $actions->filter('a');
        $delete = $actions->filter('button');

        // Exactly one anchor per row: the url hook answers for the `EditAction`
        // and declines for everything else, so the plain delete keeps its
        // confirmation modal instead of becoming a link that opens nothing.
        expect($edit->count())->toBe(1)
            ->and($edit->text())->toContain('Edit')
            ->and($edit->attr('href'))->toEndWith(sprintf('/admin/articles/%s/edit', $key))
            ->and($delete->count())->toBe(1)
            ->and($delete->attr('data-live-name-param'))->toBe('delete');
    }
});

it('mounts a header action through actions method discovery', function (): void {
    [$client] = seededArticlesClient();

    $component = $this->createLiveComponent(ListArticles::class, client: $client)
        ->call('mountAction', ['name' => 'purge', 'arguments' => [], 'context' => []]);

    expect((string) $component->render())->toContain('Are you sure you would like to do this?');

    expect((string) $component->call('callMountedAction')->render())->toContain('Purged');
});

it('deletes a record through the row action and re-renders without it', function (): void {
    [$client, $entityManager] = seededArticlesClient();

    $recordKey = articleKey($entityManager, 'beta');

    $component = $this->createLiveComponent(ListArticles::class, client: $client)
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => $recordKey]]);

    expect((string) $component->render())->toContain('Are you sure you would like to do this?');

    $rendered = $component->call('callMountedAction')->render();
    $rows = $rendered->crawler()->filter('tbody .sy-ta-row');

    $entityManager->clear();

    // The success toast repeats the deleted title, so the row count is what
    // proves the re-render dropped it, not the absence of the word.
    expect($rows->count())->toBe(1)
        ->and($rows->text())->toContain('Alpha')
        ->and((string) $rendered)->toContain('sylament:notification')
        ->and($entityManager->getRepository(Article::class)->findOneBy(['slug' => 'beta']))->toBeNull();
});
