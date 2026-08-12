<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListArticles;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
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

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('h1.sy-pa-header-heading')->text())->toBe('Articles')
        ->and($crawler->filter('.sy-pa-resource-list-records-page .sy-pa-page-content .sy-ta')->count())->toBe(1)
        ->and($html)->toContain('Alpha')
        ->and($html)->toContain('published')
        ->and($html)->toContain('<title>Articles — Test Admin</title>');
});

it('renders the header actions the page declares', function (): void {
    [$client] = seededArticlesClient();

    $crawler = $client->request('GET', '/admin/articles');

    expect($crawler->filter('.sy-pa-header-actions button')->text())->toContain('Purge');
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
