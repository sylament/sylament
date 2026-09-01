<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Pages\OptedOutNavigationPage;
use Sylament\Panels\Tests\Fixtures\Resources\LineResource;
use Sylament\Panels\Tests\Fixtures\Resources\VerseResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

it('resolves page item urls and active state through container services', function (): void {
    $panel = $this::getContainer()->get(PanelRegistry::class)->get('admin');

    $stack = $this::getContainer()->get(RequestStack::class);
    $request = Request::create('/admin/settings-page');
    $request->attributes->set('_route', 'sylament.admin.pages.settings-page');

    $stack->push($request);

    $navigation = $panel->getNavigation();
    $content = null;

    foreach ($navigation as $group) {
        if ($group->getLabel() === 'Content') {
            $content = $group;
        }
    }

    $settings = $content?->getItems()[0] ?? null;

    expect($settings?->getUrl())->toBe('/admin/settings-page')
        ->and($settings->isActive())->toBeTrue()
        ->and($content->isActive())->toBeTrue();

    $stack->pop();
});

/**
 * The document-wide `aria-current="page"` count is only sound because neither probe page renders a
 * table: pagination's current-page button carries the same attribute, so a list page must scope the
 * count to the sidebar instead of counting the whole document.
 */
it('renders a registered resource as a sidebar item pointing at its index page', function (): void {
    $client = $this::getContainer()->get('test.client');
    $crawler = $client->request('GET', '/probe');

    $articles = $crawler->filter('a.sy-pa-sidebar-item-btn[href="/probe/articles"]');
    $html = (string) $client->getResponse()->getContent();

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($articles->count())->toBe(1)
        ->and($articles->text())->toContain('Articles')
        ->and($crawler->filter('a.sy-pa-sidebar-item-btn[href="/probe/articles"][aria-current]')->count())->toBe(0)
        ->and($crawler->filter('a.sy-pa-sidebar-item-btn[href="/probe"][aria-current="page"]')->count())->toBe(1)
        ->and(mb_substr_count($html, 'aria-current="page"'))->toBe(1);
});

/**
 * `LineResource` is what makes this a real guard: it registers an index page of
 * its own, so only the nested check keeps it out of the sidebar.
 */
it('leaves every nested resource out of the navigation', function (): void {
    $panel = $this::getContainer()->get(PanelRegistry::class)->get('nest');

    expect($panel)->not->toBeNull();

    $labels = [];

    foreach ($panel?->getNavigation() ?? [] as $group) {
        expect($group)->toBeInstanceOf(NavigationGroup::class);

        foreach ($group->getItems() as $item) {
            expect($item)->toBeInstanceOf(NavigationItem::class);

            $labels[] = $item->getLabel();
        }
    }

    sort($labels);

    expect($labels)->toBe(['Chapters', 'Dashboard']);
});

it('keeps a nested resource registering navigation in every other sense', function (): void {
    $registry = $this::getContainer()->get(ResourceRegistry::class);

    expect($registry)->toBeInstanceOf(ResourceRegistry::class);
    assert($registry instanceof ResourceRegistry);

    $panel = $this::getContainer()->get(PanelRegistry::class)->get('nest');

    expect($panel)->not->toBeNull();
    assert($panel !== null);

    expect($registry->get(VerseResource::class)->shouldRegisterNavigation())->toBeTrue()
        ->and($registry->get(LineResource::class)->shouldRegisterNavigation())->toBeTrue()
        ->and($registry->get(LineResource::class)->getNavigationItems($panel))->toHaveCount(1);
});

/**
 * `OptedOutNavigationPage` is registered and routed like its two siblings, so only
 * the gate keeps it out of the sidebar.
 */
it('leaves a page that opts out of navigation out of the navigation', function (): void {
    $panel = $this::getContainer()->get(PanelRegistry::class)->get('admin');

    expect($panel)->not->toBeNull();

    $labels = [];

    foreach ($panel?->getNavigation() ?? [] as $group) {
        foreach ($group->getItems() as $item) {
            $labels[] = $item->getLabel();
        }
    }

    sort($labels);

    expect($labels)->toBe(['Articles', 'Dashboard', 'Notes', 'Settings'])
        ->and(OptedOutNavigationPage::shouldRegisterNavigation())->toBeFalse()
        ->and(OptedOutNavigationPage::getNavigationItems($panel))->toHaveCount(1);
});

/**
 * Same document-wide count, sound for the same reason — see the case above.
 */
it('lights the resource item on a resource page the index route only prefixes', function (): void {
    $client = $this::getContainer()->get('test.client');
    $crawler = $client->request('GET', '/probe/articles/41/probe');
    $html = (string) $client->getResponse()->getContent();

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('a.sy-pa-sidebar-item-btn[href="/probe/articles"][aria-current="page"]')->count())->toBe(1)
        ->and($crawler->filter('a.sy-pa-sidebar-item-btn[href="/probe"][aria-current]')->count())->toBe(0)
        ->and(mb_substr_count($html, 'aria-current="page"'))->toBe(1);
});
