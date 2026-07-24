<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

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

it('treats the send methods as safe no-op seams', function (): void {
    $fixture = new CanNotifyFixture()->successNotificationTitle('Deleted');

    expect($fixture->sendSuccessNotification())->toBe($fixture)
        ->and($fixture->sendFailureNotification())->toBe($fixture);
});
