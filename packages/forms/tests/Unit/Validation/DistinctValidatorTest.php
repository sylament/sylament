<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Validation;

use Sylament\Forms\Validation\Distinct;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Validation;

/**
 * @return ConstraintViolationListInterface<int, ConstraintViolationInterface>
 */
function distinctViolations(mixed $value, Distinct $constraint): ConstraintViolationListInterface
{
    return Validation::createValidator()->validate($value, $constraint);
}

it('accepts a scalar no sibling holds', function (): void {
    expect(distinctViolations('a', new Distinct(mode: Distinct::SCALAR, siblingValues: ['b', 'c'])))->toHaveCount(0);
});

it('flags a scalar a sibling holds', function (): void {
    $violations = distinctViolations('a', new Distinct(mode: Distinct::SCALAR, siblingValues: ['a']));

    expect($violations)->toHaveCount(1)
        ->and($violations->get(0)->getMessage())->toBe('This value is already used by another item.');
});

it('compares scalars loosely, so a wire string meets a filled int', function (): void {
    expect(distinctViolations('1', new Distinct(mode: Distinct::SCALAR, siblingValues: [1])))->toHaveCount(1);
});

it('flags an array intersecting a sibling and accepts a disjoint one', function (): void {
    expect(distinctViolations(['a', 'b'], new Distinct(mode: Distinct::ARRAY, siblingValues: ['b'])))->toHaveCount(1)
        ->and(distinctViolations(['a', 'b'], new Distinct(mode: Distinct::ARRAY, siblingValues: ['c'])))->toHaveCount(0);
});

it('compares nothing under the array arm when the value is not an array', function (): void {
    expect(distinctViolations('a', new Distinct(mode: Distinct::ARRAY, siblingValues: ['a'])))->toHaveCount(0);
});

it('flags a second ticked checkbox', function (): void {
    $violations = distinctViolations(true, new Distinct(mode: Distinct::BOOLEAN, isSiblingSelected: true));

    expect($violations)->toHaveCount(1)
        ->and($violations->get(0)->getMessage())->toBe('Only one item may be selected.');
});

it('accepts the only ticked checkbox', function (): void {
    expect(distinctViolations(true, new Distinct(mode: Distinct::BOOLEAN, isSiblingSelected: false)))->toHaveCount(0);
});

it('demands a selection when required and nothing is ticked anywhere', function (): void {
    $violations = distinctViolations(false, new Distinct(mode: Distinct::BOOLEAN, isSiblingSelected: false, mustBeSelected: true));

    expect($violations)->toHaveCount(1)
        ->and($violations->get(0)->getMessage())->toBe('At least one item must be selected.');
});

it('accepts nothing ticked when not required', function (): void {
    expect(distinctViolations(false, new Distinct(mode: Distinct::BOOLEAN, isSiblingSelected: false, mustBeSelected: false)))->toHaveCount(0);
});

it('accepts an unticked checkbox when a sibling is ticked, even when required', function (): void {
    expect(distinctViolations(false, new Distinct(mode: Distinct::BOOLEAN, isSiblingSelected: true, mustBeSelected: true)))->toHaveCount(0);
});

it('returns on an absent value in every mode', function (): void {
    foreach ([Distinct::SCALAR, Distinct::ARRAY, Distinct::BOOLEAN] as $mode) {
        expect(distinctViolations(null, new Distinct(mode: $mode, siblingValues: [null], mustBeSelected: true)))->toHaveCount(0)
            ->and(distinctViolations('', new Distinct(mode: $mode, siblingValues: [''], mustBeSelected: true)))->toHaveCount(0);
    }
});

it('refuses an unknown mode at construction', function (): void {
    expect(fn (): Distinct => new Distinct(mode: 'nope'))
        ->toThrow(InvalidArgumentException::class, 'The distinct comparison mode [nope] is unknown.');
});

it('propagates each custom message', function (): void {
    $scalar = distinctViolations('a', new Distinct(mode: Distinct::SCALAR, siblingValues: ['a'], message: 'Dup.'));
    $only = distinctViolations(true, new Distinct(mode: Distinct::BOOLEAN, isSiblingSelected: true, onlyOneMustBeSelectedMessage: 'One only.'));
    $must = distinctViolations(false, new Distinct(mode: Distinct::BOOLEAN, mustBeSelected: true, mustBeSelectedMessage: 'Pick one.'));

    expect($scalar->get(0)->getMessage())->toBe('Dup.')
        ->and($only->get(0)->getMessage())->toBe('One only.')
        ->and($must->get(0)->getMessage())->toBe('Pick one.');
});
