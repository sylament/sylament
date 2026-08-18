<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use Sylament\Forms\Components\Select;

it('defaults the loading message to its English literal', function (): void {
    expect(Select::make('author')->getLoadingMessage())->toBe('Loading...');
});

it('overrides the loading message with a literal or a closure', function (): void {
    expect(Select::make('author')->loadingMessage('Fetching...')->getLoadingMessage())->toBe('Fetching...')
        ->and(Select::make('author')->loadingMessage(fn (): string => 'One moment')->getLoadingMessage())->toBe('One moment');
});

it('falls back to the literal when given null', function (): void {
    expect(Select::make('author')->loadingMessage('Fetching...')->loadingMessage(null)->getLoadingMessage())->toBe('Loading...');
});
