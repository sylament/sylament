<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use Sylament\Forms\Components\DatePicker;
use Sylament\Forms\Components\DateTimePicker;
use Sylament\Forms\Components\TimePicker;

it('is native by default', function (): void {
    expect(DateTimePicker::make('publishedAt')->isNative())->toBeTrue();
});

it('takes a literal condition', function (): void {
    expect(DateTimePicker::make('publishedAt')->native(false)->isNative())->toBeFalse()
        ->and(DateTimePicker::make('publishedAt')->native()->isNative())->toBeTrue();
});

it('evaluates a closure condition', function (): void {
    expect(DateTimePicker::make('publishedAt')->native(fn (): bool => false)->isNative())->toBeFalse()
        ->and(DateTimePicker::make('publishedAt')->native(fn (): bool => true)->isNative())->toBeTrue();
});

it('is composed by every picker', function (): void {
    expect(DatePicker::make('day')->isNative())->toBeTrue()
        ->and(TimePicker::make('alarm')->isNative())->toBeTrue();
});
