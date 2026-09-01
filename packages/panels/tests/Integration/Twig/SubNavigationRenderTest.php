<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Enums\SubNavigationPosition;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Pages\SubNavigationProbePage;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateArticle;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditArticle;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListArticles;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

beforeEach(function (): void {
    SubNavigationProbePage::probe(null);
});

/*
 * The three resource pages carry process-wide statics, so every case that turns one
 * on hands it back before the next file renders the same page.
 */
afterEach(function (): void {
    ListArticles::probe(null);
    CreateArticle::probe(null);
    EditArticle::probe(null);
});

/**
 * Two loose items — the **second** of them active, so every active-state assertion
 * tells the active item apart from the first one — plus one labelled group, so every
 * arm of every variant has something to draw.
 *
 * @return array<NavigationItem|NavigationGroup>
 */
function probeSubNavigation(): array
{
    return [
        NavigationItem::make('Overview')->url('/admin/overview'),
        NavigationItem::make('Details')->url('/admin/details')->isActiveWhen(fn (): bool => true),
        NavigationGroup::make('More')->items([
            NavigationItem::make('Audit')->url('/admin/audit'),
        ]),
    ];
}

it('draws the tabs variant with anchors, one active tab and the group dropdown', function (): void {
    SubNavigationProbePage::probe(SubNavigationPosition::Top, probeSubNavigation());

    $crawler = $this->createLiveComponent(SubNavigationProbePage::getComponentName())->render()->crawler();
    $tabs = $crawler->filter('.sy-pa-page-main > nav.sy-tabs.sy-pa-sub-navigation-tabs');

    expect($tabs->count())->toBe(1)
        ->and($tabs->filter('a.sy-tabs-item[aria-current="page"]')->count())->toBe(1)
        ->and($tabs->filter('a.sy-tabs-item[aria-current="page"]')->attr('href'))->toBe('/admin/details')
        ->and($tabs->filter('a.sy-tabs-item[href="/admin/overview"]')->count())->toBe(1)
        ->and($tabs->filter('.sy-dropdown .sy-dropdown-trigger .sy-tabs-item')->text())->toBe('More')
        ->and($tabs->filter('.sy-dropdown .sy-dropdown-panel a.sy-dropdown-item[href="/admin/audit"]')->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-sub-navigation-sidebar-ctn')->count())->toBe(0);
});

it('draws the sidebar variant before the content at the start edge', function (): void {
    SubNavigationProbePage::probe(SubNavigationPosition::Start, probeSubNavigation());

    $crawler = $this->createLiveComponent(SubNavigationProbePage::getComponentName())->render()->crawler();

    expect($crawler->filter('.sy-pa-page-main > .sy-pa-sub-navigation-sidebar-ctn + .sy-pa-page-content')->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-sub-navigation-sidebar-ctn > ul.sy-pa-sub-navigation-sidebar > li.sy-pa-sidebar-group')->count())->toBe(2)
        ->and($crawler->filter('.sy-pa-sub-navigation-tabs')->count())->toBe(0);
});

it('draws the sidebar variant after the content at the end edge', function (): void {
    SubNavigationProbePage::probe(SubNavigationPosition::End, probeSubNavigation());

    $crawler = $this->createLiveComponent(SubNavigationProbePage::getComponentName())->render()->crawler();

    expect($crawler->filter('.sy-pa-page-main > .sy-pa-page-content + .sy-pa-sub-navigation-sidebar-ctn')->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-page-main > .sy-pa-sub-navigation-sidebar-ctn + .sy-pa-page-content')->count())->toBe(0);
});

it('keeps the sub-navigation sidebar groups expanded and off the sidebar controller', function (): void {
    SubNavigationProbePage::probe(SubNavigationPosition::Start, probeSubNavigation());

    $crawler = $this->createLiveComponent(SubNavigationProbePage::getComponentName())->render()->crawler();
    $sidebar = $crawler->filter('.sy-pa-sub-navigation-sidebar-ctn');

    expect($sidebar->filter('[aria-expanded]')->count())->toBe(0)
        ->and($sidebar->filter('[data-action]')->count())->toBe(0)
        ->and($sidebar->filter('ul#sy-pa-sidebar-group-items-sub-0')->count())->toBe(1)
        ->and($sidebar->filter('ul#sy-pa-sidebar-group-items-sub-1')->count())->toBe(1)
        ->and($sidebar->filter('li.sy-pa-sidebar-item a[aria-current="page"]')->count())->toBe(1)
        ->and($sidebar->filter('li.sy-pa-sidebar-item a[aria-current="page"]')->attr('href'))->toBe('/admin/details');
});

it('draws the mobile dropdown in every position, labelled by the active item', function (SubNavigationPosition $position): void {
    SubNavigationProbePage::probe($position, probeSubNavigation());

    $crawler = $this->createLiveComponent(SubNavigationProbePage::getComponentName())->render()->crawler();
    $dropdown = $crawler->filter('.sy-pa-page-main > .sy-dropdown.sy-pa-sub-navigation-dropdown');

    expect($dropdown->count())->toBe(1)
        ->and($dropdown->filter('.sy-dropdown-trigger .sy-btn')->text())->toBe('Details')
        ->and($dropdown->filter('.sy-dropdown-panel a.sy-dropdown-item')->count())->toBe(3)
        ->and($dropdown->filter('.sy-dropdown-panel a.sy-dropdown-item[aria-current="page"]')->count())->toBe(1);
})->with([
    [SubNavigationPosition::Start],
    [SubNavigationPosition::End],
    [SubNavigationPosition::Top],
]);

it('marks the page root with the band and its position', function (SubNavigationPosition $position): void {
    SubNavigationProbePage::probe($position, probeSubNavigation());

    $crawler = $this->createLiveComponent(SubNavigationProbePage::getComponentName())->render()->crawler();

    expect($crawler->filter('.sy-pa-page.sy-pa-page-has-sub-navigation.sy-pa-page-has-sub-navigation-' . $position->value)->count())->toBe(1);
})->with([
    [SubNavigationPosition::Start],
    [SubNavigationPosition::End],
    [SubNavigationPosition::Top],
]);

/**
 * The resource partials are served over HTTP the way `ListRecordsTest` serves them —
 * reboot disabled, since the fixture database lives in memory on a container service.
 *
 * @return array{KernelBrowser, string}
 */
function subNavigationArticlesClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    EntityManagerFactory::seed($entityManager, new Article()->setTitle('Alpha')->setSlug('alpha'));

    $article = $entityManager->getRepository(Article::class)->findOneBy(['slug' => 'alpha']);

    expect($article)->toBeInstanceOf(Article::class);

    return [$client, (string) $article?->getId()];
}

/**
 * Each resource partial owns its own copy of the band block, so each is asserted whole:
 * the root modifiers, exactly one sidebar on the briefed side of the content and exactly
 * one mobile menu — the counts are what catch a partial whose second tag lost `:end`.
 */
function expectResourceSubNavigationBand(Crawler $crawler, string $pageClass, SubNavigationPosition $position): void
{
    $edge = $position === SubNavigationPosition::Start
        ? '.sy-pa-page-main > .sy-pa-sub-navigation-sidebar-ctn + .sy-pa-page-content'
        : '.sy-pa-page-main > .sy-pa-page-content + .sy-pa-sub-navigation-sidebar-ctn';

    expect($crawler->filter('.sy-pa-page.' . $pageClass . '.sy-pa-page-has-sub-navigation.sy-pa-page-has-sub-navigation-' . $position->value)->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-sub-navigation-sidebar-ctn')->count())->toBe(1)
        ->and($crawler->filter($edge)->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-sub-navigation-dropdown')->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-sub-navigation-sidebar-ctn a[aria-current="page"]')->attr('href'))->toBe('/admin/details');
}

it('draws the band on the resource list page', function (SubNavigationPosition $position): void {
    [$client] = subNavigationArticlesClient();
    ListArticles::probe($position, probeSubNavigation());

    $crawler = $client->request('GET', '/admin/articles');

    expect($client->getResponse()->getStatusCode())->toBe(200);

    expectResourceSubNavigationBand($crawler, 'sy-pa-resource-list-records-page', $position);
})->with([
    [SubNavigationPosition::Start],
    [SubNavigationPosition::End],
]);

it('draws the band on the resource create page', function (SubNavigationPosition $position): void {
    [$client] = subNavigationArticlesClient();
    CreateArticle::probe($position, probeSubNavigation());

    $crawler = $client->request('GET', '/admin/articles/create');

    expect($client->getResponse()->getStatusCode())->toBe(200);

    expectResourceSubNavigationBand($crawler, 'sy-pa-resource-create-record-page', $position);
})->with([
    [SubNavigationPosition::Start],
    [SubNavigationPosition::End],
]);

it('draws the band on the resource edit page', function (SubNavigationPosition $position): void {
    [$client, $key] = subNavigationArticlesClient();
    EditArticle::probe($position, probeSubNavigation());

    $crawler = $client->request('GET', '/admin/articles/' . $key . '/edit');

    expect($client->getResponse()->getStatusCode())->toBe(200);

    expectResourceSubNavigationBand($crawler, 'sy-pa-resource-edit-record-page', $position);
})->with([
    [SubNavigationPosition::Start],
    [SubNavigationPosition::End],
]);

it('draws no band at all for a page with no sub-navigation', function (): void {
    SubNavigationProbePage::probe(SubNavigationPosition::Top);

    $crawler = $this->createLiveComponent(SubNavigationProbePage::getComponentName())->render()->crawler();

    expect($crawler->filter('.sy-pa-page')->count())->toBe(1)
        ->and($crawler->filter('[class*="sy-pa-page-has-sub-navigation"]')->count())->toBe(0)
        ->and($crawler->filter('.sy-pa-sub-navigation-dropdown')->count())->toBe(0)
        ->and($crawler->filter('.sy-pa-sub-navigation-tabs')->count())->toBe(0)
        ->and($crawler->filter('.sy-pa-sub-navigation-sidebar-ctn')->count())->toBe(0);
});
