<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Validation;

use Sylament\Forms\Validation\Prohibited;
use Symfony\Component\Validator\Validation;

function prohibitedViolations(mixed $value, ?string $message = null): int
{
    return count(Validation::createValidator()->validate($value, new Prohibited(message: $message)));
}

it('accepts every shape of absence', function (): void {
    expect(prohibitedViolations(null))->toBe(0)
        ->and(prohibitedViolations(''))->toBe(0)
        ->and(prohibitedViolations('  '))->toBe(0)
        ->and(prohibitedViolations([]))->toBe(0);
});

it('flags every shape of presence, false and zero included', function (): void {
    expect(prohibitedViolations('x'))->toBe(1)
        ->and(prohibitedViolations(0))->toBe(1)
        ->and(prohibitedViolations(false))->toBe(1)
        ->and(prohibitedViolations(['a']))->toBe(1);
});

it('carries the default message', function (): void {
    $violations = Validation::createValidator()->validate('x', new Prohibited());

    expect($violations->get(0)->getMessage())->toBe('This value is prohibited.');
});

it('propagates a custom message', function (): void {
    $violations = Validation::createValidator()->validate('x', new Prohibited(message: 'Not here.'));

    expect($violations->get(0)->getMessage())->toBe('Not here.');
});
