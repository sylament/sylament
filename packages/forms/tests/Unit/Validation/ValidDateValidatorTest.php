<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Validation;

use Sylament\Forms\Validation\ValidDate;
use Symfony\Component\Validator\Validation;

it('accepts null', function (): void {
    expect(Validation::createValidator()->validate(null, new ValidDate()))->toHaveCount(0);
});

it('accepts the empty string', function (): void {
    expect(Validation::createValidator()->validate('', new ValidDate()))->toHaveCount(0);
});

it('accepts a date string', function (): void {
    expect(Validation::createValidator()->validate('2026-08-18', new ValidDate()))->toHaveCount(0);
});

it('accepts the datetime-local string a browser submits', function (): void {
    expect(Validation::createValidator()->validate('2026-08-18T14:30', new ValidDate()))->toHaveCount(0);
});

it('accepts a time-only string', function (): void {
    expect(Validation::createValidator()->validate('14:30', new ValidDate()))->toHaveCount(0);
});

it('flags an unparseable string', function (): void {
    $violations = Validation::createValidator()->validate('banana', new ValidDate());

    expect($violations)->toHaveCount(1)
        ->and($violations->get(0)->getMessage())->toBe('This value is not a valid date.');
});

it('flags a non-string value', function (): void {
    expect(Validation::createValidator()->validate(42, new ValidDate()))->toHaveCount(1);
});

it('flags an array', function (): void {
    expect(Validation::createValidator()->validate([], new ValidDate()))->toHaveCount(1);
});

it('carries a message the caller overrides', function (): void {
    $constraint = new ValidDate();
    $constraint->message = 'Not a date.';

    $violations = Validation::createValidator()->validate('banana', $constraint);

    expect($violations->get(0)->getMessage())->toBe('Not a date.');
});
