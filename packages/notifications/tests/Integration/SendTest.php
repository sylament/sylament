<?php

declare(strict_types=1);

use Sylament\Notifications\Delivery\SessionNotifications;
use Sylament\Notifications\Notification;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

it('send() pushes toArray() onto the session flash', function (): void {
    $container = $this::getContainer();

    $request = new Request();
    $request->setSession(new Session(new MockArraySessionStorage()));
    $container->get(RequestStack::class)->push($request);

    Notification::make('n1')->title('Hi')->success()->send();

    $pulled = $container->get(SessionNotifications::class)->pull();

    expect($pulled)->toHaveCount(1)
        ->and($pulled[0]['id'])->toBe('n1')
        ->and($pulled[0]['title'])->toBe('Hi')
        ->and($pulled[0]['status'])->toBe('success');
});

it('send() fails fast on an instance built outside the container', function (): void {
    $this::getContainer();

    new Notification()->id('n1')->send();
})->throws(LogicException::class);
