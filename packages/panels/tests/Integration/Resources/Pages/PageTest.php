<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The fixture database lives in memory on a container service, so a reboot
 * between two requests would hand the second one a freshly-migrated, empty one.
 *
 * @return array{KernelBrowser, string}
 */
function previewProbeClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    EntityManagerFactory::seed($entityManager, new Article()->setTitle('Alpha')->setSlug('alpha'));

    $article = $entityManager->getRepository(Article::class)->findOneBy(['slug' => 'alpha']);

    expect($article)->toBeInstanceOf(Article::class);

    return [$client, (string) $article?->getId()];
}

it('serves a resource page a user wrote, with its record read through the body block', function (): void {
    [$client, $key] = previewProbeClient();

    $crawler = $client->request('GET', sprintf('/probe/articles/%s/preview', $key));

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('[data-probe-preview="Alpha"]')->count())->toBe(1);
});

it('trails the resource and the page above a record the resource titles by nothing', function (): void {
    [$client, $key] = previewProbeClient();

    $crawler = $client->request('GET', sprintf('/probe/articles/%s/preview', $key));

    expect($crawler->filter('.sy-breadcrumbs li')->each(fn ($crumb): string => mb_trim($crumb->text())))
        ->toBe(['Articles', 'Preview']);
});

it('lists the page in its resource band, active, beside a header link back to the index', function (): void {
    [$client, $key] = previewProbeClient();

    $crawler = $client->request('GET', sprintf('/probe/articles/%s/preview', $key));

    expect($crawler->filter('.sy-pa-sub-navigation-sidebar a')->each(fn ($item): string => mb_trim($item->text())))
        ->toBe(['Preview'])
        ->and($crawler->filter('.sy-pa-sub-navigation-sidebar a[aria-current="page"]')->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-header-actions a')->attr('href'))->toEndWith('/probe/articles');
});

it('refuses a record key the resource matches nothing with', function (): void {
    [$client] = previewProbeClient();

    $client->request('GET', '/probe/articles/missing/preview');

    expect($client->getResponse()->getStatusCode())->toBe(404);
});
