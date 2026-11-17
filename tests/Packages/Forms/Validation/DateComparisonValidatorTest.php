<?php

declare(strict_types=1);

use Sylament\Forms\Validation\DateComparison;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

it('refuses a value on the wrong side of its boundary per operator, naming the boundary', function (string $operator, string $value, array $messages): void {
    $violations = $this::getContainer()->get(ValidatorInterface::class)->validate($value, new DateComparison($operator, new DateTimeImmutable('2026-01-01 08:00:00')));

    expect(array_map(static fn (ConstraintViolationInterface $violation): string => (string) $violation->getMessage(), iterator_to_array($violations)))->toBe($messages);
})->with([
    'after, before the boundary' => [DateComparison::AFTER, '2026-01-01 07:59:59', ['This value must be after 2026-01-01 08:00:00.']],
    'after, on it' => [DateComparison::AFTER, '2026-01-01 08:00:00', ['This value must be after 2026-01-01 08:00:00.']],
    'after, past it' => [DateComparison::AFTER, '2026-01-01 08:00:01', []],
    'after or equal, before the boundary' => [DateComparison::AFTER_OR_EQUAL, '2026-01-01 07:59:59', ['This value must be on or after 2026-01-01 08:00:00.']],
    'after or equal, on it' => [DateComparison::AFTER_OR_EQUAL, '2026-01-01 08:00:00', []],
    'before, past the boundary' => [DateComparison::BEFORE, '2026-01-01 08:00:01', ['This value must be before 2026-01-01 08:00:00.']],
    'before, on it' => [DateComparison::BEFORE, '2026-01-01 08:00:00', ['This value must be before 2026-01-01 08:00:00.']],
    'before, ahead of it' => [DateComparison::BEFORE, '2026-01-01 07:59:59', []],
    'before or equal, past the boundary' => [DateComparison::BEFORE_OR_EQUAL, '2026-01-01 08:00:01', ['This value must be on or before 2026-01-01 08:00:00.']],
    'before or equal, on it' => [DateComparison::BEFORE_OR_EQUAL, '2026-01-01 08:00:00', []],
]);

it('reports the message it is given in place of its default', function (): void {
    $violations = $this::getContainer()->get(ValidatorInterface::class)->validate('2025-01-01', new DateComparison(DateComparison::AFTER, new DateTimeImmutable('2026-01-01'), message: 'Too early: {{ boundary }}.'));

    expect($violations)->toHaveCount(1)
        ->and((string) $violations->get(0)->getMessage())->toBe('Too early: 2026-01-01 00:00:00.');
});

it('leaves a blank, unparseable or non-string value to the date constraint', function (mixed $value): void {
    expect($this::getContainer()->get(ValidatorInterface::class)->validate($value, new DateComparison(DateComparison::AFTER, new DateTimeImmutable('2026-01-01'))))->toHaveCount(0);
})->with([
    'a blank string' => ['   '],
    'a value no parse accepts' => ['tampered'],
    'null' => [null],
    'a value that is no string' => [20200101],
]);
