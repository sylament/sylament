<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use LogicException;
use Sylament\Actions\Tests\Fixtures\Concerns\CanNotifyFixture;

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
