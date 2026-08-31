<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;
use Sylament\Panels\Twig\Components\GlobalSearch;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Kernel reboot is disabled for every flow here: the fixture database is an
 * in-memory SQLite built by a container service, so a reboot between two
 * requests would hand the second one a freshly-migrated, empty database — the
 * shape `ListRecordsTest` established.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function globalSearchComponentClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    assert($entityManager instanceof EntityManagerInterface);

    $author = new Author()->setName('Ada Lovelace');

    EntityManagerFactory::seed(
        $entityManager,
        $author,
        new Article()->setTitle('Alpha post')->setSlug('alpha-post')->setStatus('draft')->setAuthor($author),
        new Article()->setTitle('Beta post')->setSlug('beta-post')->setStatus('published'),
    );

    return [$client, $entityManager];
}

function globalSearchComponentArticlesLabel(): string
{
    $registry = test()->getContainer()->get(ResourceRegistry::class);
    assert($registry instanceof ResourceRegistry);

    return $registry->get(ArticleResource::class)->getPluralRecordLabel();
}

it('stamps the current panel onto the component when it mounts', function (): void {
    [$client] = globalSearchComponentClient();

    $component = $this->createLiveComponent(GlobalSearch::class, client: $client)->component();

    expect($component->panelId)->toBe('admin')
        ->and($component->getPanel()->getId())->toBe('admin');
});

it('keeps the search controller off the live root', function (): void {
    [$client] = globalSearchComponentClient();

    $crawler = $this->createLiveComponent(GlobalSearch::class, client: $client)->render()->crawler();

    // `attrs()` replaces `data-controller` rather than appending to it, so the
    // live root keeps its own binding and the search controller rides the inner
    // element every target and action lives inside.
    expect($crawler->filter('.sy-pa-global-search-ctn')->attr('data-controller'))->toBe('live')
        ->and($crawler->filter('.sy-pa-global-search')->attr('data-controller'))->toBe('sylament--panels--global-search')
        ->and($crawler->filter('.sy-pa-global-search-results-ctn')->count())->toBe(0);
});

it('binds the field to the panel debounce, the key bindings and the search prop', function (): void {
    [$client] = globalSearchComponentClient();

    $crawler = $this->createLiveComponent(GlobalSearch::class, client: $client)->render()->crawler();

    $input = $crawler->filter('#sy-pa-global-search-input');

    expect($input->attr('data-model'))->toBe('debounce(500)|search')
        ->and($input->attr('type'))->toBe('search')
        ->and($input->attr('data-sylament--panels--global-search-target'))->toBe('input')
        ->and($input->attr('data-action'))->toContain('keydown.down->sylament--panels--global-search#focusFirstResult')
        ->and($crawler->filter('.sy-pa-global-search')->attr('data-sylament--panels--global-search-key-bindings-value'))->toBe('[]')
        ->and($crawler->filter('label.sy-pa-global-search-label')->attr('for'))->toBe('sy-pa-global-search-input')
        ->and($crawler->filter('.sy-input-wrp-prefix svg')->count())->toBe(1);
});

it('groups the results of a hit under the plural record label', function (): void {
    [$client, $entityManager] = globalSearchComponentClient();

    $rendered = $this->createLiveComponent(GlobalSearch::class, client: $client)
        ->set('search', 'alpha')
        ->render();

    $crawler = $rendered->crawler();
    $article = $entityManager->getRepository(Article::class)->findOneBy(['slug' => 'alpha-post']);
    $link = $crawler->filter('.sy-pa-global-search-result-link');

    expect($article)->toBeInstanceOf(Article::class)
        ->and($crawler->filter('.sy-pa-global-search-results-ctn')->attr('data-sylament--panels--global-search-target'))->toBe('resultsPanel')
        ->and($crawler->filter('.sy-pa-global-search-result-group-header')->text())->toBe(globalSearchComponentArticlesLabel())
        ->and($link->count())->toBe(1)
        ->and($link->attr('href'))->toEndWith('/admin/articles/' . $article?->getId() . '/edit')
        ->and($link->attr('data-action'))->toContain('sylament--panels--global-search#close')
        ->and($crawler->filter('.sy-pa-global-search-result-heading')->text())->toBe('Alpha post')
        ->and($crawler->filter('.sy-pa-global-search-result-detail-label')->text())->toBe('Status:')
        ->and($crawler->filter('.sy-pa-global-search-result-detail-value')->text())->toBe('draft')
        // The resource hands every result a record-bound `preview` action, so the
        // row wears the has-actions class and renders the action beside the link.
        ->and($crawler->filter('li.sy-pa-global-search-result-has-actions')->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-global-search-result-actions a')->attr('href'))->toBe('/preview');
});

it('finds a record through an association dot-path', function (): void {
    [$client] = globalSearchComponentClient();

    $crawler = $this->createLiveComponent(GlobalSearch::class, client: $client)
        ->set('search', 'lovelace')
        ->render()
        ->crawler();

    expect($crawler->filter('.sy-pa-global-search-result-heading')->text())->toBe('Alpha post');
});

it('answers the no-results message for a term nothing matches', function (): void {
    [$client] = globalSearchComponentClient();

    $crawler = $this->createLiveComponent(GlobalSearch::class, client: $client)
        ->set('search', 'zzz-no-hit')
        ->render()
        ->crawler();

    expect($crawler->filter('.sy-pa-global-search-no-results-message')->text())->toBe('No search results found.')
        ->and($crawler->filter('.sy-pa-global-search-results')->count())->toBe(0);
});

it('renders no results panel at all for a blank search', function (): void {
    [$client] = globalSearchComponentClient();

    $component = $this->createLiveComponent(GlobalSearch::class, client: $client)
        ->set('search', 'alpha');

    expect($component->render()->crawler()->filter('.sy-pa-global-search-results-ctn')->count())->toBe(1)
        // A whitespace-only search trims to blank, so the panel goes away again
        // rather than answering an empty result set.
        ->and($component->set('search', '   ')->render()->crawler()->filter('.sy-pa-global-search-results-ctn')->count())->toBe(0);
});

it('mounts the field in the topbar of a panel page', function (): void {
    [$client] = globalSearchComponentClient();

    $crawler = $client->request('GET', '/admin');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('.sy-pa-topbar-end #sy-pa-global-search-input')->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-topbar-end .sy-pa-global-search-ctn')->attr('data-controller'))->toBe('live');
});
