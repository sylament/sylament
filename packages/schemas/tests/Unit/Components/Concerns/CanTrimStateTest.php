<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Tests\Fixtures\Components\MutatingField;

it('does not trim by default', function (): void {
    expect(MutatingField::make('a')->isTrimmed())->toBeFalse()
        ->and(MutatingField::make('a')->exposeTrimState('  x  '))->toBe('  x  ');
});

it('trims unicode whitespace when trimmed', function (): void {
    expect(MutatingField::make('a')->trim()->exposeTrimState("\u{00A0} x \u{2003}"))->toBe('x');
});

it('leaves a non-string alone', function (): void {
    expect(MutatingField::make('a')->trim()->exposeTrimState(['  x  ']))->toBe(['  x  ']);
});

it('evaluates a closure condition', function (): void {
    expect(MutatingField::make('a')->trim(fn (): bool => false)->isTrimmed())->toBeFalse()
        ->and(MutatingField::make('a')->trim(fn (): bool => true)->isTrimmed())->toBeTrue();
});

it('memoises the evaluated condition', function (): void {
    $calls = 0;
    $field = MutatingField::make('a')->trim(function () use (&$calls): bool {
        $calls++;

        return true;
    });

    $field->isTrimmed();
    $field->isTrimmed();

    expect($calls)->toBe(1);
});
