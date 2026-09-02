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

/**
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function tabbedArticlesClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    EntityManagerFactory::seed(
        $entityManager,
        new Article()->setTitle('Alpha')->setSlug('alpha'),
        new Article()->setTitle('Beta')->setSlug('beta')->setStatus('published'),
        new Article()->setTitle('Gamma')->setSlug('gamma')->setStatus('published'),
    );

    return [$client, $entityManager];
}

/**
 * @return list<string>
 */
function tabbedRowTitles(Crawler $crawler): array
{
    return $crawler->filter('tbody .sy-ta-row')->each(fn (Crawler $row): string => $row->text());
}

it('renders the tab strip above the table', function (): void {
    [$client] = tabbedArticlesClient();

    $crawler = $client->request('GET', '/admin/articles');

    $strip = $crawler->filter('.sy-pa-page-content > .sy-sc .sy-sc-tabs');
    $labels = $strip->filter('.sy-tabs-item')->each(fn (Crawler $item): string => mb_trim($item->text()));

    expect($strip->count())->toBe(1)
        // The generated label rides the key of the tab that declares none, and
        // the declared one is left alone.
        ->and($labels)->toBe(['All', 'Live', 'In review'])
        ->and($strip->filter('[data-model="activeTab"]')->count())->toBe(3)
        ->and($strip->filter('[data-value="published"]')->count())->toBe(1);
});

it('renders no strip for a list declaring no tabs', function (): void {
    [$client] = tabbedArticlesClient();

    $crawler = $client->request('GET', '/admin/notes');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('.sy-sc-tabs')->count())->toBe(0);
});

it('activates the first tab and lists every record under it', function (): void {
    [$client] = tabbedArticlesClient();

    $crawler = $client->request('GET', '/admin/articles');

    expect($crawler->filter('.sy-sc-tabs .sy-tabs-item')->eq(0)->attr('aria-selected'))->toBe('true')
        ->and(tabbedRowTitles($crawler))->toHaveCount(3);
});

it('narrows the list to the tab the query string names', function (): void {
    [$client] = tabbedArticlesClient();

    $crawler = $client->request('GET', '/admin/articles?tab=published');
    $titles = implode(' ', tabbedRowTitles($crawler));

    expect(tabbedRowTitles($crawler))->toHaveCount(2)
        ->and($titles)->toContain('Beta')
        ->and($titles)->toContain('Gamma')
        ->and($titles)->not->toContain('Alpha')
        ->and($crawler->filter('.sy-sc-tabs .sy-tabs-item')->eq(1)->attr('aria-selected'))->toBe('true');
});

it('heals a bookmarked tab the list no longer declares', function (): void {
    [$client] = tabbedArticlesClient();

    $crawler = $client->request('GET', '/admin/articles?tab=nope');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and(tabbedRowTitles($crawler))->toHaveCount(3);
});

it('resets the page when the active tab changes', function (): void {
    [$client] = tabbedArticlesClient();

    [$client] = tabbedArticlesClient();

    $component = $this->createLiveComponent(ListArticles::class, client: $client)
        ->call('gotoTablePage', ['page' => 2]);

    expect($component->component()->tablePage)->toBe(2);

    $component->set('activeTab', 'published');

    expect($component->component()->tablePage)->toBe(1)
        ->and($component->component()->activeTab)->toBe('published');
});
