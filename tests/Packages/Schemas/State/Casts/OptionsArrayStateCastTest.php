<?php

declare(strict_types=1);

use Sylament\Schemas\State\Casts\OptionsArrayStateCast;
use Sylament\Tests\Fixtures\Schemas\IntBackedNumber;

it('reads each element of a selection as an option', function (): void {
    expect(new OptionsArrayStateCast()->get([
        'x' => '12',
        'y' => '01',
        'z' => IntBackedNumber::Two,
        new class implements Stringable {
            public function __toString(): string
            {
                return '7';
            }
        },
        PHP_INT_MIN,
    ]))->toBe([12, '01', 2, 7, PHP_INT_MIN]);
});

it('drops a nested-list or object element and keeps the rest of the selection', function (): void {
    $cast = new OptionsArrayStateCast();

    expect($cast->get(['1', ['x'], new stdClass(), '2']))->toBe([1, 2])
        ->and($cast->set(['1', ['x'], new stdClass(), 2]))->toBe(['1', '2']);
});

it('reads and writes blank state as an empty list, or as no selection when nullable', function (bool $isNullable, ?array $expected): void {
    $cast = $isNullable ? new OptionsArrayStateCast(isNullable: true) : new OptionsArrayStateCast();

    foreach ([null, '', []] as $state) {
        expect($cast->get($state))->toBe($expected)
            ->and($cast->set($state))->toBe($expected);
    }
})->with([
    'by default' => [false, []],
    'nullable' => [true, null],
]);

it('wraps a single value into a one-element selection', function (): void {
    expect(new OptionsArrayStateCast()->get('3'))->toBe([3]);
});

it('writes a list of strings', function (): void {
    expect(new OptionsArrayStateCast()->set(['x' => 7, 'y' => IntBackedNumber::Ten, 'z' => '01']))->toBe(['7', '10', '01']);
});
