<?php

declare(strict_types=1);

use Sylament\Forms\Validation\RequiredValue;
use Symfony\Component\Validator\Validator\ValidatorInterface;

it("refuses Laravel's blank set and takes every other value, false and zero included", function (mixed $value, bool $passes): void {
    $violations = $this::getContainer()->get(ValidatorInterface::class)->validate($value, new RequiredValue());

    expect(count($violations))->toBe($passes ? 0 : 1);

    if (!$passes) {
        expect($violations->get(0)->getMessage())->toBe('This value should not be blank.');
    }
})->with([
    'null' => [null, false],
    'an empty string' => ['', false],
    'whitespace-only text' => [" \t\u{3000}", false],
    'an empty list' => [[], false],
    'false' => [false, true],
    'zero' => [0, true],
    "the string '0'" => ['0', true],
    'text' => ['x', true],
    'a list' => [['x'], true],
]);
