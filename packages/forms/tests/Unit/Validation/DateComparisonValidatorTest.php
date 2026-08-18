<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Validation;

use DateTimeImmutable;
use Sylament\Forms\Validation\DateComparison;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Validation;

function compare(mixed $value, string $operator, string $boundary = '2026-08-18 00:00:00'): int
{
    return count(Validation::createValidator()->validate(
        $value,
        new DateComparison($operator, new DateTimeImmutable($boundary)),
    ));
}

it('accepts a value after the boundary', function (): void {
    expect(compare('2026-08-19', DateComparison::AFTER))->toBe(0);
});

it('flags a value before the boundary', function (): void {
    $violations = Validation::createValidator()->validate(
        '2026-08-17',
        new DateComparison(DateComparison::AFTER, new DateTimeImmutable('2026-08-18 00:00:00')),
    );

    expect($violations)->toHaveCount(1)
        ->and($violations->get(0)->getMessage())->toBe('This value must be after 2026-08-18 00:00:00.');
});

it('flags an equal value under after but accepts it under after or equal', function (): void {
    expect(compare('2026-08-18', DateComparison::AFTER))->toBe(1)
        ->and(compare('2026-08-18', DateComparison::AFTER_OR_EQUAL))->toBe(0);
});

it('accepts a value before the boundary under before', function (): void {
    expect(compare('2026-08-17', DateComparison::BEFORE))->toBe(0)
        ->and(compare('2026-08-19', DateComparison::BEFORE))->toBe(1);
});

it('flags an equal value under before but accepts it under before or equal', function (): void {
    expect(compare('2026-08-18', DateComparison::BEFORE))->toBe(1)
        ->and(compare('2026-08-18', DateComparison::BEFORE_OR_EQUAL))->toBe(0);
});

it('compares the wall time of a datetime-local value', function (): void {
    expect(compare('2026-08-18T09:00', DateComparison::AFTER, '2026-08-18 10:00:00'))->toBe(1)
        ->and(compare('2026-08-18T11:00', DateComparison::AFTER, '2026-08-18 10:00:00'))->toBe(0);
});

it('skips a blank value', function (): void {
    expect(compare(null, DateComparison::AFTER))->toBe(0)
        ->and(compare('', DateComparison::AFTER))->toBe(0);
});

it('skips an unparseable value, which ValidDate owns', function (): void {
    expect(compare('banana', DateComparison::AFTER))->toBe(0);
});

it('skips a non-string value', function (): void {
    expect(compare(42, DateComparison::AFTER))->toBe(0);
});

it('carries a per-operator default message', function (): void {
    $boundary = new DateTimeImmutable('2026-08-18 00:00:00');

    expect(new DateComparison(DateComparison::AFTER, $boundary)->message)
        ->toBe('This value must be after {{ boundary }}.')
        ->and(new DateComparison(DateComparison::AFTER_OR_EQUAL, $boundary)->message)
        ->toBe('This value must be on or after {{ boundary }}.')
        ->and(new DateComparison(DateComparison::BEFORE, $boundary)->message)
        ->toBe('This value must be before {{ boundary }}.')
        ->and(new DateComparison(DateComparison::BEFORE_OR_EQUAL, $boundary)->message)
        ->toBe('This value must be on or before {{ boundary }}.');
});

it('takes a message the caller passes', function (): void {
    $constraint = new DateComparison(
        DateComparison::AFTER,
        new DateTimeImmutable('2026-08-18 00:00:00'),
        'Pick a later day than {{ boundary }}.',
    );

    $violations = Validation::createValidator()->validate('2026-08-17', $constraint);

    expect($violations->get(0)->getMessage())->toBe('Pick a later day than 2026-08-18 00:00:00.');
});

it('rejects an unknown operator at construction', function (): void {
    expect(fn (): DateComparison => new DateComparison('sideways', new DateTimeImmutable('2026-08-18')))
        ->toThrow(InvalidArgumentException::class, 'sideways');
});
