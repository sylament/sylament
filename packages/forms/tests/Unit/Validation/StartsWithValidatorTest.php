<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Validation;

use Sylament\Forms\Validation\StartsWith;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Validation;

/**
 * @param  list<string> $values
 * @return ConstraintViolationListInterface<int, ConstraintViolationInterface>
 */
function startsWithViolations(mixed $value, array $values, bool $match = true): ConstraintViolationListInterface
{
    return Validation::createValidator()->validate($value, new StartsWith(values: $values, match: $match));
}

it('accepts a value starting with any of the prefixes', function (): void {
    expect(startsWithViolations('SY-1', ['SY-', 'sy-']))->toHaveCount(0);
});

it('flags a value starting with none of them', function (): void {
    $violations = startsWithViolations('X-1', ['SY-', 'sy-']);

    expect($violations)->toHaveCount(1)
        ->and($violations->get(0)->getMessage())->toBe('This value must start with one of: SY-, sy-.');
});

it('inverts under match false', function (): void {
    $violations = startsWithViolations('SY-1', ['SY-', 'sy-'], match: false);

    expect($violations)->toHaveCount(1)
        ->and($violations->get(0)->getMessage())->toBe('This value must not start with any of: SY-, sy-.')
        ->and(startsWithViolations('X-1', ['SY-', 'sy-'], match: false))->toHaveCount(0);
});

it('stringifies a numeric value', function (): void {
    expect(startsWithViolations(12, ['1']))->toHaveCount(0);
});

it('flags a value that is neither string nor numeric in both polarities', function (): void {
    expect(startsWithViolations(['x'], ['x']))->toHaveCount(1)
        ->and(startsWithViolations(['x'], ['x'], match: false))->toHaveCount(1);
});

it('returns on an absent value', function (): void {
    expect(startsWithViolations(null, ['x']))->toHaveCount(0)
        ->and(startsWithViolations('', ['x']))->toHaveCount(0);
});

it('refuses an empty value list at construction', function (): void {
    expect(fn (): StartsWith => new StartsWith(values: []))
        ->toThrow(InvalidArgumentException::class, 'needs at least one value');
});
