<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Concerns;

use RuntimeException;
use Sylament\Actions\Events\ActionCalled;
use Sylament\Actions\Events\ActionCalling;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The two lifecycle events are dispatched as real objects on Symfony's
 * dispatcher — Filament dispatches the class-string with the Action as the
 * payload, so its listeners never see the event object at all.
 */

it('dispatches ActionCalling then ActionCalled around one call, both carrying the action', function (): void {
    $dispatcher = $this::getContainer()->get('event_dispatcher');

    $seen = [];

    $dispatcher->addListener(ActionCalling::class, function (ActionCalling $event) use (&$seen): void {
        $seen[] = $event;
    });
    $dispatcher->addListener(ActionCalled::class, function (ActionCalled $event) use (&$seen): void {
        $seen[] = $event;
    });

    $host = $this->createLiveComponent('ActionHost')->component();
    $action = $host->getAction('notify');

    $host->mountAction('notify');

    expect($seen)->toHaveCount(2)
        ->and($seen[0])->toBeInstanceOf(ActionCalling::class)
        ->and($seen[1])->toBeInstanceOf(ActionCalled::class)
        ->and($seen[0]->getAction())->toBe($action)
        ->and($seen[1]->getAction())->toBe($action);
});

it('dispatches ActionCalled even when the after() closure throws', function (): void {
    $dispatcher = $this::getContainer()->get('event_dispatcher');

    $seen = [];

    $dispatcher->addListener(ActionCalled::class, function (ActionCalled $event) use (&$seen): void {
        $seen[] = $event;
    });

    $host = $this->createLiveComponent('ActionHost')->component();
    $host->getAction('notify')?->after(function (): never {
        throw new RuntimeException('after blew up');
    });

    expect(fn (): mixed => $host->mountAction('notify'))
        ->toThrow(RuntimeException::class, 'after blew up');

    expect($seen)->toHaveCount(1);
});
