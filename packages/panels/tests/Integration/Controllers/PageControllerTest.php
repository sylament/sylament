<?php

declare(strict_types=1);

use Sylament\Panels\Controllers\PageController;
use Sylament\Panels\Pages\Page;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class UnregisteredPage extends Page
{
}

function invokePageController(ContainerInterface $container, Request $request): void
{
    $requests = $container->get(RequestStack::class);
    $controller = $container->get(PageController::class);

    $requests->push($request);

    try {
        $controller($request);
    } finally {
        $requests->pop();
    }
}

it('serves the dashboard inside the shell', function (): void {
    $client = $this::getContainer()->get('test.client');
    $client->request('GET', '/admin');

    $response = $client->getResponse();
    $html = (string) $response->getContent();

    expect($response->getStatusCode())->toBe(200)
        ->and($html)->toContain('id="sy-pa-sidebar"')
        ->and($html)->toContain('Test Admin')
        ->and($html)->toContain('<title>Dashboard — Test Admin</title>')
        ->and($html)->toContain('sy-pa-page')
        ->and($html)->toContain('aria-current="page"')
        ->and($html)->toContain('data-live-name-value="Sylament:Panels:Pages:Dashboard"');
});

it('serves a second page with its own navigation state', function (): void {
    $client = $this::getContainer()->get('test.client');
    $client->request('GET', '/admin/settings-page');

    $html = (string) $client->getResponse()->getContent();

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($html)->toContain('Settings Page')
        ->and(mb_substr_count($html, 'aria-current="page"'))->toBe(1);
});

it('refuses a request that names no panel', function (): void {
    $request = Request::create('/admin');
    $request->attributes->set('_sylament_page', UnregisteredPage::class);

    expect(fn () => invokePageController($this::getContainer(), $request))
        ->toThrow(LogicException::class, '`_sylament_panel`');
});

it('refuses a request whose page default is not a page', function (): void {
    $request = Request::create('/admin');
    $request->attributes->set('_sylament_panel', 'admin');
    $request->attributes->set('_sylament_page', stdClass::class);

    expect(fn () => invokePageController($this::getContainer(), $request))
        ->toThrow(LogicException::class, '`_sylament_page`');
});

it('refuses a page class that is not a registered service', function (): void {
    $request = Request::create('/admin');
    $request->attributes->set('_sylament_panel', 'admin');
    $request->attributes->set('_sylament_page', UnregisteredPage::class);

    expect(fn () => invokePageController($this::getContainer(), $request))
        ->toThrow(LogicException::class, 'is not a registered service');
});
