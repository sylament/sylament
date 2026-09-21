<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Imprint;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ImprintProbePage;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Kernel reboot is disabled throughout: the fixture database lives in memory on
 * a container service, so a reboot between two requests would hand the second
 * one a freshly-migrated, empty one.
 */

/**
 * The seeded imprint's identifier carries a dot, a slash and a space, so every
 * URL below is the escaped grammar rather than the plain pair.
 *
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function compositeCustomPageClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $catalogue = new Catalogue()->setTitle('First');

    EntityManagerFactory::seed(
        $entityManager,
        $catalogue,
        new Imprint()->setHouse('www.example.com')->setYear('/blog 2026')->setLabel('Escaped')->setCatalogue($catalogue),
        new Imprint()->setHouse('plain')->setYear('about')->setLabel('Plain'),
    );

    return [$client, $entityManager];
}

/**
 * A resource read in-process resolves the *current* panel, so a case reading
 * one outside a kernel request stands a request carrying this panel up first.
 */
function compositeCustomPagePanel(): void
{
    $request = new Request();
    $request->attributes->set('_sylament_panel', 'key');

    test()->getContainer()->get(RequestStack::class)->push($request);
}

it('serves a custom record page over a composite record and renders the key it was mounted with', function (): void {
    [$client] = compositeCustomPageClient();

    $crawler = $client->request('GET', '/key/imprints/www=2Eexample=2Ecom~=2Fblog=202026/probe');
    $probe = $crawler->filter('[data-probe-key]');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($probe->attr('data-probe-key'))->toBe('www=2Eexample=2Ecom~=2Fblog=202026')
        ->and($probe->attr('data-probe-edit-url'))->toBe('http://localhost/key/imprints/www=2Eexample=2Ecom~=2Fblog=202026/edit')
        ->and($probe->attr('data-probe-edit-url'))->not->toContain('%');
});

it('draws the record band over the composite key on every sibling page', function (): void {
    [$client] = compositeCustomPageClient();

    $crawler = $client->request('GET', '/key/imprints/www=2Eexample=2Ecom~=2Fblog=202026/probe');
    $items = $crawler->filter('.sy-pa-sub-navigation-sidebar-ctn a');
    $active = $crawler->filter('.sy-pa-sub-navigation-sidebar-ctn a[aria-current="page"]');

    expect($items->each(fn (Crawler $item): string => $item->text() . '|' . $item->attr('href')))->toBe([
        'Edit Imprint|/key/imprints/www=2Eexample=2Ecom~=2Fblog=202026/edit',
        'Probe|/key/imprints/www=2Eexample=2Ecom~=2Fblog=202026/probe',
    ])
        ->and($active->count())->toBe(1)
        ->and($active->text())->toBe('Probe');
});

it('mints a sibling page url from the composite record the page holds', function (): void {
    [$client] = compositeCustomPageClient();

    compositeCustomPagePanel();

    $page = $this->createLiveComponent(ImprintProbePage::class, ['record' => 'www=2Eexample=2Ecom~=2Fblog=202026', 'panelId' => 'key'], client: $client)
        ->component();

    // The record parameter is filled from the key the page carries for every
    // name but the index, which takes none.
    expect($page->getRecordKey())->toBe('www=2Eexample=2Ecom~=2Fblog=202026')
        ->and($page->getResourceUrl('edit'))->toBe('/key/imprints/www=2Eexample=2Ecom~=2Fblog=202026/edit')
        ->and($page->getResourceUrl('index'))->toBe('/key/imprints');
});

it('declares no record class token of its own on a custom record page', function (): void {
    [$client] = compositeCustomPageClient();

    $crawler = $client->request('GET', '/key/imprints/www=2Eexample=2Ecom~=2Fblog=202026/probe');

    // `sy-pa-resource-record-<key>` is declared by the two resource pages that
    // build a class list of their own; a custom record page inherits the empty
    // one, so the key reaches the markup through the page's body alone.
    expect($crawler->filter('.sy-pa-page')->attr('class'))
        ->toBe('sy sy-pa-page sy-pa-page-has-sub-navigation sy-pa-page-has-sub-navigation-start');
});
