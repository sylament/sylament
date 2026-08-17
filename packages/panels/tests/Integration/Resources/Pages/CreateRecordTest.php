<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateArticle;
use Sylament\Schemas\Exceptions\ValidationException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Kernel reboot is disabled for every flow here: the fixture database is an
 * in-memory SQLite built by a container service, and the created record has to
 * survive from the live `create` request into the redirect the browser follows —
 * the shape `ListRecordsTest` and the tables suite established. The same client
 * is also what carries the session, which is what makes the flashed toast
 * readable on the next page load.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function createRecordClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    return [$client, test()->getContainer()->get(EntityManagerInterface::class)];
}

it('serves the create page with the resource form', function (): void {
    [$client] = createRecordClient();

    $crawler = $client->request('GET', '/admin/articles/create');
    $html = (string) $client->getResponse()->getContent();

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('h1.sy-pa-header-heading')->text())->toBe('Create Article')
        ->and($crawler->filter('.sy-pa-resource-create-record-page > .sy-pa-page-header-main-ctn > .sy-pa-page-main > .sy-pa-page-content > form')->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-resource-create-record-page input[data-model$="data.title"]')->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-resource-form-actions button[type="submit"]')->text())->toContain('Create')
        ->and($html)->toContain('<title>Create Article — Test Admin</title>');
});

it('trails the resource above the create heading', function (): void {
    [$client] = createRecordClient();

    $crawler = $client->request('GET', '/admin/articles/create');

    $items = $crawler->filter('.sy-pa-header-has-breadcrumbs .sy-breadcrumbs-item');

    expect($items->count())->toBe(2)
        ->and($items->eq(0)->filter('a.sy-breadcrumbs-item-label')->attr('href'))->toEndWith('/admin/articles')
        ->and($items->eq(0)->filter('a.sy-breadcrumbs-item-label')->text())->toBe('Articles')
        ->and($items->eq(1)->filter('span.sy-breadcrumbs-item-label')->text())->toBe('Create')
        ->and($items->eq(1)->filter('span.sy-breadcrumbs-item-label')->attr('aria-current'))->toBe('page');
});

it('creates a record, flashes the toast and redirects to the edit page', function (): void {
    [$client, $entityManager] = createRecordClient();

    $component = $this->createLiveComponent(CreateArticle::class, client: $client);
    $component->set('data', ['title' => 'Gamma', 'slug' => 'gamma']);
    $component->call('create');

    $entityManager->clear();
    $article = $entityManager->getRepository(Article::class)->findOneBy(['slug' => 'gamma']);

    expect($article)->toBeInstanceOf(Article::class)
        ->and($article?->getTitle())->toBe('Gamma')
        ->and($component->response()->isRedirection())->toBeTrue();

    $location = (string) $component->response()->headers->get('Location');

    expect($location)->toEndWith(sprintf('/admin/articles/%d/edit', $article?->getId()));

    $crawler = $client->request('GET', $location);
    $toasts = $crawler->filter('.sy-no[data-controller~="sylament--notifications--notifications"] .sy-no-notification');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($toasts->count())->toBe(1)
        ->and($toasts->text())->toContain('Created');
});

it('rejects an invalid submit with a validation error and persists nothing', function (): void {
    [$client, $entityManager] = createRecordClient();

    $component = $this->createLiveComponent(CreateArticle::class, client: $client);
    $component->set('data', ['title' => '', 'slug' => '']);

    // `TestLiveComponent` forces `catchExceptions(false)`, so the 422 escapes
    // `call()` as the exception rather than reaching BrowserKit as a response.
    expect(fn (): mixed => $component->call('create'))
        ->toThrow(ValidationException::class);

    $entityManager->clear();

    expect($entityManager->getRepository(Article::class)->count([]))->toBe(0);
});
