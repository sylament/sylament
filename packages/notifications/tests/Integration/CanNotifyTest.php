<?php

declare(strict_types=1);

namespace Sylament\Notifications\Tests\Integration;

use Sylament\Actions\Action;
use Sylament\Actions\BulkAction;
use Sylament\Notifications\Delivery\SessionNotifications;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/*
 * The SUT spans two packages: the code under test is actions'
 * `Concerns\CanNotify`, but its notification arm is a guarded soft integration
 * — `sylament/actions` only *suggests* `sylament/notifications` — so only a
 * kernel registering both bundles can exercise it, and this suite is the one
 * that does. Like tables' `RecordActionsResolutionTest`, the file sits with the
 * package that completes the seam rather than mirroring `src/`, and it is the
 * rename-pin for the soft reference: a moved or renamed `Notification` leaves
 * `class_exists()` false and every action toast silently disappears, which
 * fails here instead.
 */

function flashSession(): SessionNotifications
{
    $container = test()->getContainer();

    $request = new Request();
    $request->setSession(new Session(new MockArraySessionStorage()));
    $container->get(RequestStack::class)->push($request);

    return $container->get(SessionNotifications::class);
}

it('sends a success toast carrying the action title', function (): void {
    $notifications = flashSession();

    Action::make('delete')->successNotificationTitle('Deleted')->sendSuccessNotification();

    $pulled = $notifications->pull();

    expect($pulled)->toHaveCount(1)
        ->and($pulled[0]['title'])->toBe('Deleted')
        ->and($pulled[0]['status'])->toBe('success');
});

it('sends a persistent danger toast on failure', function (): void {
    $notifications = flashSession();

    Action::make('delete')->failureNotificationTitle('Could not delete')->sendFailureNotification();

    $pulled = $notifications->pull();

    expect($pulled)->toHaveCount(1)
        ->and($pulled[0]['title'])->toBe('Could not delete')
        ->and($pulled[0]['status'])->toBe('danger')
        ->and($pulled[0]['duration'])->toBe('persistent');
});

it('evaluates a title closure before sending', function (): void {
    $notifications = flashSession();

    Action::make('delete')
        ->successNotificationTitle(fn (Action $action): string => "Deleted {$action->getName()}")
        ->sendSuccessNotification();

    expect($notifications->pull()[0]['title'])->toBe('Deleted delete');
});

it('sends nothing when the action has no title', function (): void {
    $notifications = flashSession();

    Action::make('delete')->sendSuccessNotification();
    Action::make('delete')->sendFailureNotification();

    expect($notifications->pull())->toBe([]);
});

it('stays chainable', function (): void {
    flashSession();

    $action = Action::make('delete')->successNotificationTitle('Deleted');

    expect($action->sendSuccessNotification())->toBe($action)
        ->and($action->sendFailureNotification())->toBe($action);
});

it('downgrades a partial bulk failure to a warning toast', function (): void {
    $notifications = flashSession();

    $action = BulkAction::make('delete')
        ->failureNotificationTitle(fn (int $successCount): string => "Deleted {$successCount}");
    $action->reportBulkProcessingSuccessfulRecordsCount(2);
    $action->sendFailureNotification();

    $pulled = $notifications->pull();

    expect($pulled[0]['title'])->toBe('Deleted 2')
        ->and($pulled[0]['status'])->toBe('warning')
        ->and($pulled[0]['duration'])->toBe('persistent');
});

it('keeps a total bulk failure a danger toast', function (): void {
    $notifications = flashSession();

    $action = BulkAction::make('delete')->failureNotificationTitle('Failed to delete');
    $action->reportCompleteBulkProcessingFailure();
    $action->sendFailureNotification();

    expect($notifications->pull()[0]['status'])->toBe('danger');
});

it('keeps a non-bulk failure a danger toast even with a moved successful count', function (): void {
    $notifications = flashSession();

    $action = Action::make('delete')->failureNotificationTitle('Could not delete');
    $action->reportBulkProcessingSuccessfulRecordsCount(2);
    $action->sendFailureNotification();

    expect($notifications->pull()[0]['status'])->toBe('danger');
});
