<?php

declare(strict_types=1);

use Sylament\Schemas\State\Casts\OptionStateCast;
use Sylament\Tests\Fixtures\Schemas\IntBackedNumber;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;

it('reads and writes blank state as no option, or an empty string when not nullable', function (): void {
    $cast = new OptionStateCast();
    $notNullable = new OptionStateCast(isNullable: false);

    expect($cast->get(null))->toBeNull()
        ->and($cast->get('  '))->toBeNull()
        ->and($cast->get([]))->toBeNull()
        ->and($cast->set(''))->toBeNull()
        ->and($notNullable->get(''))->toBe('')
        ->and($notNullable->set(''))->toBe('');
});

it('reads and writes a payload that is not an option as no option', function (mixed $state): void {
    $cast = new OptionStateCast();

    expect($cast->get($state))->toBeNull()
        ->and($cast->set($state))->toBeNull();
})->with([
    'a list' => [['1']],
    'an object' => [new stdClass()],
]);

it('reads an enum as its backing value and a Stringable as its string', function (): void {
    $cast = new OptionStateCast();

    expect($cast->get(IntBackedNumber::One))->toBe(1)
        ->and($cast->get(StringBackedLetter::A))->toBe('a')
        ->and($cast->get(new class implements Stringable {
            public function __toString(): string
            {
                return '12';
            }
        }))->toBe(12);
});

it('narrows a digit string to an int while it fits one and has no leading zero', function (string $state, int|string $expected): void {
    expect(new OptionStateCast()->get($state))->toBe($expected);
})->with([
    'plain' => ['12', 12],
    'zero' => ['0', 0],
    'a leading zero' => ['01', '01'],
    'the widest int' => [(string) PHP_INT_MAX, PHP_INT_MAX],
    'one above the widest int' => ['9223372036854775808', '9223372036854775808'],
    'much wider' => ['123456789012345678901234', '123456789012345678901234'],
    'not digits' => ['draft', 'draft'],
]);

it('keeps an int state an int, whatever its width', function (): void {
    $cast = new OptionStateCast();

    expect($cast->get(PHP_INT_MAX))->toBe(PHP_INT_MAX)
        ->and($cast->get(PHP_INT_MIN))->toBe(PHP_INT_MIN);
});

it('writes the option as a string', function (): void {
    $cast = new OptionStateCast();

    expect($cast->set(7))->toBe('7')
        ->and($cast->set(true))->toBe('1')
        ->and($cast->set(false))->toBe('')
        ->and($cast->set(IntBackedNumber::Ten))->toBe('10');
});
