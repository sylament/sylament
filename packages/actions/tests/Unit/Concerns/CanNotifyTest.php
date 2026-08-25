<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use LogicException;
use Sylament\Actions\Tests\Fixtures\Concerns\CanNotifyFixture;
use Sylament\Notifications\Notification;

it('stores the success and failure notification titles', function (): void {
    $fixture = new CanNotifyFixture()
        ->successNotificationTitle('Deleted')
        ->failureNotificationTitle('Could not delete');

    expect($fixture->getSuccessNotificationTitle())->toBe('Deleted')
        ->and($fixture->getFailureNotificationTitle())->toBe('Could not delete');
});

it('has null notification titles by default', function (): void {
    expect(new CanNotifyFixture()->getSuccessNotificationTitle())->toBeNull()
        ->and(new CanNotifyFixture()->getFailureNotificationTitle())->toBeNull();
});

it('sends nothing, chainably, when there is no title to send', function (): void {
    $fixture = new CanNotifyFixture();

    expect($fixture->sendSuccessNotification())->toBe($fixture)
        ->and($fixture->sendFailureNotification())->toBe($fixture);
});

it('fails fast when a titled send runs outside the container', function (): void {
    expect(fn (): mixed => new CanNotifyFixture()->successNotificationTitle('Deleted')->sendSuccessNotification())
        ->toThrow(LogicException::class, 'Notification::send() needs the container-built instance');
});

it('disables the success notification when successNotification(null) is passed', function (): void {
    $fixture = new CanNotifyFixture()
        ->successNotificationTitle('Saved')
        ->successNotification(null);

    expect($fixture->sendSuccessNotification())->toBe($fixture);
});

it('hands the pre-built success notification to a closure, which may suppress the send', function (): void {
    $recorded = null;

    $fixture = new CanNotifyFixture()
        ->successNotificationTitle('Saved')
        ->successNotification(function (Notification $notification) use (&$recorded): Notification {
            $recorded = $notification->getTitle();

            return $notification->title(null);
        });

    expect($fixture->sendSuccessNotification())->toBe($fixture)
        ->and($recorded)->toBe('Saved');
});

it('sends the pre-built success notification when the closure returns nothing', function (): void {
    $fixture = new CanNotifyFixture()
        ->successNotificationTitle('Saved')
        ->successNotification(fn (): null => null);

    expect(fn (): mixed => $fixture->sendSuccessNotification())
        ->toThrow(LogicException::class, 'needs the container-built instance');
});

it('disables the failure notification when failureNotification(null) is passed', function (): void {
    $fixture = new CanNotifyFixture()
        ->failureNotificationTitle('Could not delete')
        ->failureNotification(null);

    expect($fixture->sendFailureNotification())->toBe($fixture);
});

it('lets a failure closure title an otherwise untitled notification into a send', function (): void {
    $fixture = new CanNotifyFixture()
        ->failureNotification(fn (Notification $notification): Notification => $notification->title('Could not delete'));

    expect(fn (): mixed => $fixture->sendFailureNotification())
        ->toThrow(LogicException::class, 'needs the container-built instance');
});

it('spreads the failure injections into the failure notification closure', function (): void {
    $injected = [];

    $fixture = new CanNotifyFixture()
        ->failureNotificationTitle('Could not delete')
        ->failureNotification(function (Notification $notification, int $failureCount, bool $isAll, int $successCount, int $totalCount) use (&$injected): Notification {
            $injected = [
                'failureCount' => $failureCount,
                'isAll' => $isAll,
                'successCount' => $successCount,
                'totalCount' => $totalCount,
            ];

            return $notification->title(null);
        });

    expect($fixture->sendFailureNotification())->toBe($fixture)
        ->and($injected)->toBe([
            'failureCount' => 0,
            'isAll' => true,
            'successCount' => 0,
            'totalCount' => 0,
        ]);
});
