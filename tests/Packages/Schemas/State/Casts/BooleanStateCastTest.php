<?php

declare(strict_types=1);

use Sylament\Schemas\State\Casts\BooleanStateCast;

it('reads blank state as no answer when nullable, and anything else as a bool', function (bool $isNullable, mixed $state, ?bool $expected): void {
    expect(new BooleanStateCast(isNullable: $isNullable)->get($state))->toBe($expected);
})->with([
    'null, nullable' => [true, null, null],
    'whitespace, nullable' => [true, '  ', null],
    'empty list, nullable' => [true, [], null],
    'a posted zero' => [true, '0', false],
    'an int zero' => [true, 0, false],
    'any other string' => [true, 'x', true],
    'null, not nullable' => [false, null, false],
]);

it('writes blank state as no answer when nullable, and a bool or its int otherwise', function (bool $isNullable, bool $isStoredAsInt, mixed $state, bool|int|null $expected): void {
    expect(new BooleanStateCast(isNullable: $isNullable, isStoredAsInt: $isStoredAsInt)->set($state))->toBe($expected);
})->with([
    'null, nullable' => [true, false, null, null],
    'checked' => [true, false, '1', true],
    'unchecked' => [true, false, '0', false],
    'checked, stored as an int' => [true, true, '1', 1],
    'unchecked, stored as an int' => [true, true, '0', 0],
    'blank, not nullable' => [false, false, '', false],
    'blank, not nullable, stored as an int' => [false, true, null, 0],
]);
