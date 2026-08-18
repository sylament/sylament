<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use Sylament\Forms\Components\Select;

it('is not preloaded by default', function (): void {
    expect(Select::make('author')->isPreloaded())->toBeFalse();
});

it('takes a literal condition', function (): void {
    expect(Select::make('author')->preload()->isPreloaded())->toBeTrue()
        ->and(Select::make('author')->preload(false)->isPreloaded())->toBeFalse();
});

it('evaluates a closure condition', function (): void {
    expect(Select::make('author')->preload(fn (): bool => true)->isPreloaded())->toBeTrue()
        ->and(Select::make('author')->preload(fn (): bool => false)->isPreloaded())->toBeFalse();
});
