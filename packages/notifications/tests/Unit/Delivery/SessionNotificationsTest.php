<?php

declare(strict_types=1);

use Sylament\Notifications\Delivery\SessionNotifications;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

function sessionNotifications(?Session $session = null): SessionNotifications
{
    $request = new Request();
    $request->setSession($session ?? new Session(new MockArraySessionStorage()));

    $requestStack = new RequestStack();
    $requestStack->push($request);

    return new SessionNotifications($requestStack);
}

it('pushes onto the flash bag and pull() drains it', function (): void {
    $notifications = sessionNotifications();

    $notifications->push(['id' => 'a', 'title' => 'One']);
    $notifications->push(['id' => 'b', 'title' => 'Two']);

    expect($notifications->pull())->toBe([
        ['id' => 'a', 'title' => 'One'],
        ['id' => 'b', 'title' => 'Two'],
    ])
        ->and($notifications->pull())->toBe([]);
});

it('filters non-array flash entries out of pull()', function (): void {
    $session = new Session(new MockArraySessionStorage());
    $notifications = sessionNotifications($session);

    $session->getFlashBag()->add(SessionNotifications::FLASH_KEY, 'junk');
    $notifications->push(['id' => 'a']);

    expect($notifications->pull())->toBe([['id' => 'a']]);
});

it('filters flash entries carrying a non-string key out of pull()', function (): void {
    $session = new Session(new MockArraySessionStorage());
    $notifications = sessionNotifications($session);

    $session->getFlashBag()->add(SessionNotifications::FLASH_KEY, ['id' => 'a', 0 => 'junk']);
    $notifications->push(['id' => 'b']);

    expect($notifications->pull())->toBe([['id' => 'b']]);
});

it('fails fast outside a request with a session', function (): void {
    new SessionNotifications(new RequestStack())->pull();
})->throws(SessionNotFoundException::class);
