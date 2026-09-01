<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Validation;

use Sylament\Forms\Validation\RequiredValue;
use Symfony\Component\Validator\Validation;

function requiredViolations(mixed $value, ?string $message = null): int
{
    return count(Validation::createValidator()->validate($value, new RequiredValue(message: $message)));
}

it('accepts every shape of presence, false and zero included', function (): void {
    expect(requiredViolations('x'))->toBe(0)
        ->and(requiredViolations(0))->toBe(0)
        ->and(requiredViolations('0'))->toBe(0)
        ->and(requiredViolations(false))->toBe(0)
        ->and(requiredViolations(['a']))->toBe(0);
});

it('flags every shape of absence', function (): void {
    expect(requiredViolations(null))->toBe(1)
        ->and(requiredViolations(''))->toBe(1)
        ->and(requiredViolations('  '))->toBe(1)
        ->and(requiredViolations([]))->toBe(1);
});

it('carries the default message', function (): void {
    $violations = Validation::createValidator()->validate(null, new RequiredValue());

    expect($violations->get(0)->getMessage())->toBe('This value should not be blank.');
});

it('propagates a custom message', function (): void {
    $violations = Validation::createValidator()->validate(null, new RequiredValue(message: 'Give it a title.'));

    expect($violations->get(0)->getMessage())->toBe('Give it a title.');
});
