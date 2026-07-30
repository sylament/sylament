<?php

declare(strict_types=1);

use Sylament\Forms\Tests\Fixtures\Components\FixtureBooleanField;

it('is inline by default', function (): void {
    expect(FixtureBooleanField::make('agree')->isInline())->toBeTrue();
});

it('turns inline off', function (): void {
    expect(FixtureBooleanField::make('agree')->inline(false)->isInline())->toBeFalse();
});

it('evaluates a closure condition', function (): void {
    expect(FixtureBooleanField::make('agree')->inline(fn (): bool => false)->isInline())->toBeFalse();
});
