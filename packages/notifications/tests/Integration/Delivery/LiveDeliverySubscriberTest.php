<?php

declare(strict_types=1);

use Sylament\Notifications\Delivery\SessionNotifications;
use Sylament\Notifications\Notification;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

function pushLiveRequest(bool $live): void
{
    $request = new Request();
    $request->setSession(new Session(new MockArraySessionStorage()));

    if ($live) {
        $request->attributes->set('_live_component', 'NotificationsFixtures:Dummy');
    }

    test()->getContainer()->get(RequestStack::class)->push($request);
}

it('dispatches flashed notifications as browser events on a live request', function (): void {
    pushLiveRequest(live: true);

    $container = $this::getContainer();
    $container->get(SessionNotifications::class)->push(
        Notification::make('n1')->title('Saved')->success()->toArray(),
    );

    $html = (string) $this->renderTwigComponent('NotificationsFixtures:Dummy');

    expect($html)->toContain('data-live-events-to-dispatch-value')
        ->toContain('sylament:notification')
        ->toContain('sy-no-notification')
        ->and($container->get(SessionNotifications::class)->pull())->toBe([]);
});

it('leaves the flash alone when a non-live component renders during a live request', function (): void {
    pushLiveRequest(live: true);

    $container = $this::getContainer();
    $entry = Notification::make('n1')->title('Saved')->toArray();
    $container->get(SessionNotifications::class)->push($entry);

    $html = (string) $this->renderTwigComponent('NotificationsFixtures:Plain');

    expect($html)->not->toContain('sylament:notification')
        ->and($container->get(SessionNotifications::class)->pull())->toBe([$entry]);
});

it('leaves the flash alone on a non-live render, so a redirect carries it forward', function (): void {
    pushLiveRequest(live: false);

    $container = $this::getContainer();
    $entry = Notification::make('n1')->title('Saved')->toArray();
    $container->get(SessionNotifications::class)->push($entry);

    $html = (string) $this->renderTwigComponent('NotificationsFixtures:Dummy');

    expect($html)->not->toContain('sylament:notification')
        ->and($container->get(SessionNotifications::class)->pull())->toBe([$entry]);
});
