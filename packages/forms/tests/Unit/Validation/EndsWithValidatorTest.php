<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Validation;

use Sylament\Forms\Validation\EndsWith;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Validation;

/**
 * @param  list<string> $values
 * @return ConstraintViolationListInterface<int, ConstraintViolationInterface>
 */
function endsWithViolations(mixed $value, array $values, bool $match = true): ConstraintViolationListInterface
{
    return Validation::createValidator()->validate($value, new EndsWith(values: $values, match: $match));
}

it('accepts a value ending with any of the suffixes', function (): void {
    expect(endsWithViolations('1-SY', ['-SY', '-sy']))->toHaveCount(0);
});

it('flags a value ending with none of them', function (): void {
    $violations = endsWithViolations('1-X', ['-SY', '-sy']);

    expect($violations)->toHaveCount(1)
        ->and($violations->get(0)->getMessage())->toBe('This value must end with one of: -SY, -sy.');
});

it('inverts under match false', function (): void {
    $violations = endsWithViolations('1-SY', ['-SY', '-sy'], match: false);

    expect($violations)->toHaveCount(1)
        ->and($violations->get(0)->getMessage())->toBe('This value must not end with any of: -SY, -sy.')
        ->and(endsWithViolations('1-X', ['-SY', '-sy'], match: false))->toHaveCount(0);
});

it('stringifies a numeric value', function (): void {
    expect(endsWithViolations(12, ['2']))->toHaveCount(0);
});

it('flags a value that is neither string nor numeric in both polarities', function (): void {
    expect(endsWithViolations(['x'], ['x']))->toHaveCount(1)
        ->and(endsWithViolations(['x'], ['x'], match: false))->toHaveCount(1);
});

it('returns on an absent value', function (): void {
    expect(endsWithViolations(null, ['x']))->toHaveCount(0)
        ->and(endsWithViolations('', ['x']))->toHaveCount(0);
});

it('refuses an empty value list at construction', function (): void {
    expect(fn (): EndsWith => new EndsWith(values: []))
        ->toThrow(InvalidArgumentException::class, 'needs at least one value');
});
