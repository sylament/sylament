<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditArticle;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Kernel reboot is disabled for the same reason `ListRecordsTest` disables it:
 * the fixture database lives in memory on a container service, so a reboot
 * between two requests would hand the second one a freshly-migrated, empty one.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface, string}
 */
function editRecordClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    EntityManagerFactory::seed($entityManager, new Article()->setTitle('Alpha')->setSlug('alpha'));

    $article = $entityManager->getRepository(Article::class)->findOneBy(['slug' => 'alpha']);

    expect($article)->toBeInstanceOf(Article::class);

    return [$client, $entityManager, (string) $article?->getId()];
}

/**
 * @return array<string, mixed>
 */
function editRecordProps(string $html): array
{
    preg_match('/data-live-props-value="([^"]*)"/', $html, $matches);

    $props = json_decode(html_entity_decode($matches[1] ?? ''), true, flags: JSON_THROW_ON_ERROR);

    expect($props)->toBeArray();

    return $props;
}

it('serves the edit page with the record filled in', function (): void {
    [$client, , $key] = editRecordClient();

    $crawler = $client->request('GET', sprintf('/admin/articles/%s/edit', $key));
    $html = (string) $client->getResponse()->getContent();

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('h1.sy-pa-header-heading')->text())->toBe('Edit Alpha')
        ->and($crawler->filter('.sy-pa-resource-edit-record-page > .sy-pa-page-header-main-ctn > .sy-pa-page-main > .sy-pa-page-content > form')->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-resource-edit-record-page input[data-model$="data.title"]')->attr('value'))->toBe('Alpha')
        ->and($crawler->filter('.sy-pa-resource-form-actions button[type="submit"]')->text())->toContain('Save')
        // The document title is read off the un-mounted page, where the record key
        // is still null, so it carries the record *label* rather than the title.
        ->and($html)->toContain('<title>Edit Article — Test Admin</title>');
});

it('declines to link a record-bound edit action to the page already showing it', function (): void {
    [$client, , $key] = editRecordClient();

    $crawler = $client->request('GET', sprintf('/admin/articles/%s/edit', $key));
    $headerAction = $crawler->filter('.sy-pa-header-actions button');

    expect($crawler->filter('.sy-pa-header-actions a')->count())->toBe(0)
        ->and($headerAction->text())->toContain('Edit')
        ->and($headerAction->attr('data-live-name-param'))->toBe('edit');
});

it('404s for an unknown record', function (): void {
    [$client] = editRecordClient();

    $client->request('GET', '/admin/articles/999999/edit');

    expect($client->getResponse()->getStatusCode())->toBe(404);
});

it('keeps the record key on the wire, not the entity', function (): void {
    [$client, , $key] = editRecordClient();

    $component = $this->createLiveComponent(EditArticle::class, ['record' => $key], client: $client);

    expect(editRecordProps((string) $component->render())['record'])->toBe($key);
});

it('saves in place, stays on the page and dispatches the toast browser event', function (): void {
    [$client, $entityManager, $key] = editRecordClient();

    $component = $this->createLiveComponent(EditArticle::class, ['record' => $key], client: $client);
    $component->set('data', ['title' => 'Alpha, edited', 'slug' => 'alpha']);
    $component->call('save');

    expect($component->response()->isRedirection())->toBeFalse();

    $this->assertComponentDispatchBrowserEvent($component, 'sylament:notification');

    $toast = (string) ($component->getDispatchedBrowserEvent($component->render(), 'sylament:notification')['payload']['html'] ?? '');

    $entityManager->clear();
    $reloaded = $entityManager->getRepository(Article::class)->find((int) $key);

    expect($toast)->toContain('Saved')
        ->and($reloaded)->toBeInstanceOf(Article::class)
        ->and($reloaded?->getTitle())->toBe('Alpha, edited')
        ->and($entityManager->getRepository(Article::class)->count([]))->toBe(1);
});
