<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Tests\Fixtures\Components\StateField;

it('is visible by default', function (): void {
    $field = StateField::make('title');

    expect($field->isHidden())->toBeFalse()
        ->and($field->isVisible())->toBeTrue();
});

it('hides on a literal condition', function (): void {
    expect(StateField::make('title')->hidden()->isHidden())->toBeTrue()
        ->and(StateField::make('title')->hidden(false)->isHidden())->toBeFalse();
});

it('hides on a false visible() condition', function (): void {
    expect(StateField::make('title')->visible(false)->isHidden())->toBeTrue()
        ->and(StateField::make('title')->visible(false)->isVisible())->toBeFalse();
});

it('evaluates hidden()/visible() closures', function (): void {
    expect(StateField::make('title')->hidden(fn (): bool => true)->isHidden())->toBeTrue()
        ->and(StateField::make('title')->visible(fn (): bool => false)->isHidden())->toBeTrue();
});
