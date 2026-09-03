<?php

declare(strict_types=1);

use Sylament\Panels\Controllers\PageController;
use Sylament\Panels\Pages\Page;
use Sylament\Panels\Tests\Fixtures\Pages\BadLayoutPage;
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
    $crawler = $client->request('GET', '/admin');

    $response = $client->getResponse();
    $html = (string) $response->getContent();

    expect($response->getStatusCode())->toBe(200)
        ->and($html)->toContain('id="sy-pa-sidebar"')
        ->and($html)->toContain('Test Admin')
        ->and($html)->toContain('<title>Dashboard — Test Admin</title>')
        ->and($html)->toContain('sy-pa-page')
        ->and($crawler->filter('.sy-pa-page > .sy-pa-page-header-main-ctn > .sy-pa-page-main > .sy-pa-page-content')->count())->toBe(1)
        // A page outside a resource names no trail, so the header carries no
        // breadcrumbs at all rather than a single self-referring crumb.
        ->and($crawler->filter('.sy-breadcrumbs')->count())->toBe(0)
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

it('passes route parameters to the page component as props', function (): void {
    $client = $this::getContainer()->get('test.client');
    $crawler = $client->request('GET', '/probe/articles/41/probe');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('[data-probe-record="41"]')->count())->toBe(1);
});

it('leaves a resource index page without a record prop', function (): void {
    $client = $this::getContainer()->get('test.client');
    $crawler = $client->request('GET', '/probe/articles');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('[data-probe-record=""]')->count())->toBe(1);
});

it('hands a nested page its parent key under the name the route spells, and not as a prop of its own', function (): void {
    $client = $this::getContainer()->get('test.client');
    $crawler = $client->request('GET', '/nest/chapters/1/verses/7/parent-probe');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('[data-probe-record="7"][data-probe-parents="chapter=1"]')->count())->toBe(1)
        ->and($crawler->filter('[chapter]')->count())->toBe(0);
});

it('keeps a two-deep page parent keys in root-first order', function (): void {
    $client = $this::getContainer()->get('test.client');
    $crawler = $client->request('GET', '/nest/chapters/1/verses/7/lines/9/parent-probe');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('[data-probe-record="9"][data-probe-parents="chapter=1,verse=7"]')->count())->toBe(1)
        ->and($crawler->filter('[chapter], [verse]')->count())->toBe(0);
});

it('passes no parent records at all to a page outside a nesting', function (): void {
    $client = $this::getContainer()->get('test.client');
    $crawler = $client->request('GET', '/nest/chapters/5/parent-probe');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('[data-probe-record="5"][data-probe-parents="none"]')->count())->toBe(1);
});

it('turns a mount-time NotFoundHttpException into a 404 response', function (): void {
    $client = $this::getContainer()->get('test.client');
    $client->catchExceptions(true);
    $client->request('GET', '/probe/articles/missing/probe');

    expect($client->getResponse()->getStatusCode())->toBe(404);
});

it('applies the stored theme from the document head, before the stylesheets', function (): void {
    $client = $this::getContainer()->get('test.client');
    $client->request('GET', '/admin');

    $html = (string) $client->getResponse()->getContent();

    expect($html)
        ->toContain("localStorage.getItem('sylament.theme')")
        ->toContain("document.documentElement.classList.add('dark')")
        // Ahead of every stylesheet and script the head goes on to load, so the class
        // lands before the first paint rather than flashing the light theme.
        ->toMatch('#</title>\s*<script>[^<]*sylament\.theme[^<]*</script>#');
});

it('renders the notifications outlet in the document', function (): void {
    $client = $this::getContainer()->get('test.client');
    $crawler = $client->request('GET', '/admin');

    expect($crawler->filter('body > [data-controller~="sylament--notifications--notifications"]')->count())->toBe(1);
});

it('gives the shell main the panel default width class', function (): void {
    $client = $this::getContainer()->get('test.client');
    $client->request('GET', '/admin');

    expect((string) $client->getResponse()->getContent())
        ->toContain('<main id="sy-pa-main" class="sy-pa-main sy-width-7xl">');
});

it('gives the shell main the width a page declares, and the body its extra attributes', function (): void {
    $client = $this::getContainer()->get('test.client');
    $client->request('GET', '/probe/settings');

    expect((string) $client->getResponse()->getContent())
        ->toContain('<main id="sy-pa-main" class="sy-pa-main sy-width-4xl">')
        ->toContain('<body data-probe-body="settings">');
});

it('emits a width that is no token as the page own class, with no ladder prefix', function (): void {
    $client = $this::getContainer()->get('test.client');
    $client->request('GET', '/probe/wide');

    expect((string) $client->getResponse()->getContent())
        ->toContain('<main id="sy-pa-main" class="sy-pa-main max-w-[42rem]">')
        ->not->toContain('sy-width-');
});

it('wraps a simple page in the centred card shell, with no sidebar or topbar', function (): void {
    $client = $this::getContainer()->get('test.client');
    $crawler = $client->request('GET', '/probe/simple');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('body > .sy-pa-simple-layout')->count())->toBe(1)
        ->and($crawler->filter('main.sy-pa-simple-main.sy-width-md')->count())->toBe(1)
        ->and($crawler->filter('#sy-pa-sidebar, #sy-pa-topbar')->count())->toBe(0)
        ->and($crawler->filter('.sy-pa-simple-page > .sy-pa-simple-page-content > .sy-pa-simple-header')->count())->toBe(1)
        ->and((string) $client->getResponse()->getContent())->toContain('simple body');
});

it('refuses a page that names a layout the toolkit does not ship', function (): void {
    $request = Request::create('/probe/bad-layout');
    $request->attributes->set('_sylament_panel', 'probe');
    $request->attributes->set('_sylament_page', BadLayoutPage::class);

    expect(fn () => invokePageController($this::getContainer(), $request))
        ->toThrow(LogicException::class, 'Nope');
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
