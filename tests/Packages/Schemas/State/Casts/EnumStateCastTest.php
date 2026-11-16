<?php

declare(strict_types=1);

use Sylament\Schemas\State\Casts\EnumStateCast;
use Sylament\Tests\Fixtures\Schemas\IntBackedNumber;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;

it('reads blank state as no case', function (): void {
    $cast = new EnumStateCast(StringBackedLetter::class);

    expect($cast->get(null))->toBeNull()
        ->and($cast->get(''))->toBeNull()
        ->and($cast->get('  '))->toBeNull()
        ->and($cast->get([]))->toBeNull();
});

it('passes an instance of the enum through', function (): void {
    expect(new EnumStateCast(StringBackedLetter::class)->get(StringBackedLetter::B))->toBe(StringBackedLetter::B);
});

it('resolves a spelling of a backing value to its case, and anything else to no case', function (string $enum, int|string $state, ?BackedEnum $expected): void {
    expect(new EnumStateCast($enum)->get($state))->toBe($expected);
})->with([
    'a string-backed value' => [StringBackedLetter::class, 'b', StringBackedLetter::B],
    'an int-backed value' => [IntBackedNumber::class, 10, IntBackedNumber::Ten],
    'an int-backed value spelled as a string' => [IntBackedNumber::class, '10', IntBackedNumber::Ten],
    'an unknown value' => [StringBackedLetter::class, 'zz', null],
    'an int against a string-backed enum' => [StringBackedLetter::class, 1, null],
    'a word against an int-backed enum' => [IntBackedNumber::class, 'one', null],
]);

it('resolves only the canonical decimal spelling of an int backing value', function (string $state): void {
    expect(new EnumStateCast(IntBackedNumber::class)->get($state))->toBeNull();
})->with(['01', ' 1', '1 ', '+1', '1.0', '1e0']);

it('reads a payload that is neither an int nor a string as no case', function (mixed $state): void {
    expect(new EnumStateCast(IntBackedNumber::class)->get($state))->toBeNull();
})->with([
    'a list' => [['1']],
    'an object' => [new stdClass()],
    'a Stringable' => [new class implements Stringable {
        public function __toString(): string
        {
            return '1';
        }
    }],
    'true' => [true],
    'a float' => [1.0],
]);

it('writes a case as the string of its backing value and passes anything else through', function (): void {
    $cast = new EnumStateCast(IntBackedNumber::class);

    expect(new EnumStateCast(StringBackedLetter::class)->set(StringBackedLetter::A))->toBe('a')
        ->and($cast->set(IntBackedNumber::Ten))->toBe('10')
        ->and($cast->set('raw'))->toBe('raw')
        ->and($cast->set(7))->toBe(7)
        ->and($cast->set(['x']))->toBe(['x']);
});
