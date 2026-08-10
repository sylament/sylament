<?php

declare(strict_types=1);

use Sylament\Notifications\Notification;

it('defaults the duration to 6000 milliseconds', function (): void {
    expect(Notification::make('n')->getDuration())->toBe(6000);
});

it('treats a null duration as persistent', function (): void {
    expect(Notification::make('n')->duration(null)->getDuration())->toBe('persistent');
});

it('converts seconds to milliseconds and persistent() to the sentinel', function (): void {
    expect(Notification::make('n')->seconds(2.5)->getDuration())->toBe(2500)
        ->and(Notification::make('n')->persistent()->getDuration())->toBe('persistent');
});
