<?php

declare(strict_types=1);

use Sylament\Notifications\Notification;
use Sylament\Tests\Fixtures\Notifications\Selectors;

it('hands the toast controller its duration in milliseconds, six seconds unless set and 0 to stay', function (): void {
    $duration = fn (Notification $notification): ?string => $this->renderViewComponent($notification)
        ->filter(Selectors::TOAST)
        ->attr(Selectors::DURATION);

    expect($duration(Notification::make()->title('Saved')))->toBe('6000')
        ->and($duration(Notification::make()->title('Saved')->seconds(2.5)))->toBe('2500')
        ->and($duration(Notification::make()->title('Saved')->persistent()))->toBe('0')
        ->and($duration(Notification::make()->title('Saved')->duration(null)))->toBe('0');
});
