<?php

declare(strict_types=1);

use Sylament\Notifications\Delivery\SessionNotifications;
use Sylament\Notifications\Notification;
use Sylament\Support\Enums\Alignment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

function pushSessionRequest(): void
{
    $request = new Request();
    $request->setSession(new Session(new MockArraySessionStorage()));
    test()->getContainer()->get(RequestStack::class)->push($request);
}

it('renders the polite stack container with Filament default placement', function (): void {
    pushSessionRequest();

    $html = (string) $this->renderTwigComponent('SylamentNotifications:Notifications');

    expect($html)->toContain('sy-no')
        ->toContain('sy-align-right')
        ->toContain('sy-vertical-align-start')
        ->toContain('role="status"')
        ->toContain('aria-atomic="false"')
        ->toContain('data-controller="sylament--notifications--notifications"')
        ->toContain('sylament:notification@window-&gt;sylament--notifications--notifications#receive');
});

it('renders flashed notifications inline and drains the flash', function (): void {
    pushSessionRequest();

    $container = $this::getContainer();
    $container->get(SessionNotifications::class)->push(Notification::make('a')->title('One')->toArray());
    $container->get(SessionNotifications::class)->push(Notification::make('b')->title('Two')->toArray());

    $html = (string) $this->renderTwigComponent('SylamentNotifications:Notifications');

    expect(mb_substr_count($html, 'sy-no-notification-title'))->toBe(2)
        ->and($html)->toContain('One')->toContain('Two')
        ->and($container->get(SessionNotifications::class)->pull())->toBe([]);
});

it('accepts alignment as enum or string', function (): void {
    pushSessionRequest();

    $html = (string) $this->renderTwigComponent('SylamentNotifications:Notifications', [
        'alignment' => Alignment::Center,
        'verticalAlignment' => 'end',
    ]);

    expect($html)->toContain('sy-align-center')->toContain('sy-vertical-align-end');
});
