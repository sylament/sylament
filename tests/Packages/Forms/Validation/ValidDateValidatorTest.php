<?php

declare(strict_types=1);

use Sylament\Forms\Validation\ValidDate;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

it('refuses a value no date parse accepts or that is no string, and passes a lenient spelling or a blank', function (mixed $value, array $messages): void {
    $violations = $this::getContainer()->get(ValidatorInterface::class)->validate($value, new ValidDate());

    expect(array_map(static fn (ConstraintViolationInterface $violation): string => (string) $violation->getMessage(), iterator_to_array($violations)))->toBe($messages);
})->with([
    'a date in the wire format' => ['2026-08-18 14:30:00', []],
    "the browser's T spelling without seconds" => ['2026-08-18T14:30', []],
    'a relative date' => ['tomorrow', []],
    'a value no parse accepts' => ['tampered', ['This value is not a valid date.']],
    'null' => [null, []],
    'an empty string' => ['', []],
    'a value that is no string' => [20260818, ['This value is not a valid date.']],
]);
