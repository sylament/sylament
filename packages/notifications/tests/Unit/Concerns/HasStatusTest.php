<?php

declare(strict_types=1);

use Sylament\Notifications\Notification;

it('stores a status and evaluates a closure status', function (): void {
    expect(Notification::make('n')->status('success')->getStatus())->toBe('success')
        ->and(Notification::make('n')->status(fn (): string => 'info')->getStatus())->toBe('info')
        ->and(Notification::make('n')->getStatus())->toBeNull();
});

it('sets the status through the four sugar methods', function (): void {
    expect(Notification::make('n')->success()->getStatus())->toBe('success')
        ->and(Notification::make('n')->info()->getStatus())->toBe('info')
        ->and(Notification::make('n')->warning()->getStatus())->toBe('warning')
        ->and(Notification::make('n')->danger()->getStatus())->toBe('danger');
});
