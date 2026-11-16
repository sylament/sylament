<?php

declare(strict_types=1);

use Sylament\Schemas\State\Casts\EnumArrayStateCast;
use Sylament\Tests\Fixtures\Schemas\IntBackedNumber;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;

it('reads and writes blank state as an empty list', function (mixed $state): void {
    $cast = new EnumArrayStateCast(StringBackedLetter::class);

    expect($cast->get($state))->toBe([])
        ->and($cast->set($state))->toBe([]);
})->with([
    'null' => [null],
    'an empty string' => [''],
    'an empty list' => [[]],
]);

it('resolves each element of a selection to its case', function (): void {
    expect(new EnumArrayStateCast(StringBackedLetter::class)->get(['a', StringBackedLetter::B]))->toBe([StringBackedLetter::A, StringBackedLetter::B])
        ->and(new EnumArrayStateCast(IntBackedNumber::class)->get(['1', 10]))->toBe([IntBackedNumber::One, IntBackedNumber::Ten]);
});

it('drops an element no case matches and keeps the rest as a list', function (): void {
    expect(new EnumArrayStateCast(StringBackedLetter::class)->get(['a', 'zz', '', true, 1.5, 'b']))->toBe([StringBackedLetter::A, StringBackedLetter::B]);
});

it('wraps a single value into a one-element selection', function (): void {
    expect(new EnumArrayStateCast(StringBackedLetter::class)->get('a'))->toBe([StringBackedLetter::A]);
});

it('writes a list of strings, dropping blank and non-scalar elements', function (): void {
    expect(new EnumArrayStateCast(IntBackedNumber::class)->set([IntBackedNumber::Ten, '2', 5, ['x'], '', null]))->toBe(['10', '2', '5']);
});
