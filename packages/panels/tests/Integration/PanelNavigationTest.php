<?php

declare(strict_types=1);

use Sylament\Panels\PanelRegistry;
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

it('renders a registered resource as a sidebar item pointing at its index page', function (): void {
    $client = $this::getContainer()->get('test.client');
    $crawler = $client->request('GET', '/probe');

    $articles = $crawler->filter('a.sy-pa-sidebar-item-btn[href="/probe/articles"]');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($articles->count())->toBe(1)
        ->and($articles->text())->toContain('Articles')
        ->and($crawler->filter('a.sy-pa-sidebar-item-btn[href="/probe/articles"][aria-current]')->count())->toBe(0)
        ->and($crawler->filter('a.sy-pa-sidebar-item-btn[href="/probe"][aria-current="page"]')->count())->toBe(1);
});

it('lights the resource item on a resource page the index route only prefixes', function (): void {
    $client = $this::getContainer()->get('test.client');
    $crawler = $client->request('GET', '/probe/articles/41/probe');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('a.sy-pa-sidebar-item-btn[href="/probe/articles"][aria-current="page"]')->count())->toBe(1)
        ->and($crawler->filter('a.sy-pa-sidebar-item-btn[href="/probe"][aria-current]')->count())->toBe(0);
});
