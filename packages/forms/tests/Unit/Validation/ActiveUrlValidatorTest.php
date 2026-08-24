<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Validation;

use Sylament\Forms\Validation\ActiveUrl;
use Sylament\Forms\Validation\ActiveUrlValidator;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * The seam D15 exists for: the suite must never touch the network, and a real
 * lookup would make it non-hermetic and slow.
 */
final class FakeDnsActiveUrlValidator extends ActiveUrlValidator
{
    protected function hasDnsRecord(string $host): bool
    {
        return $host === 'resolves.test';
    }
}

/**
 * @return ConstraintViolationListInterface<int, ConstraintViolationInterface>
 */
function activeUrlViolations(mixed $value): ConstraintViolationListInterface
{
    return Validation::createValidatorBuilder()
        ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
            ActiveUrlValidator::class => new FakeDnsActiveUrlValidator(),
        ]))
        ->getValidator()
        ->validate($value, new ActiveUrl());
}

it('accepts a URL whose host resolves', function (): void {
    expect(activeUrlViolations('https://resolves.test/path'))->toHaveCount(0);
});

it('flags a URL whose host does not resolve', function (): void {
    $violations = activeUrlViolations('https://nope.test');

    expect($violations)->toHaveCount(1)
        ->and($violations->get(0)->getMessage())->toBe('This value is not an active URL.');
});

it('flags a value with no host at all', function (): void {
    expect(activeUrlViolations('not a url'))->toHaveCount(1);
});

it('flags a value that is not a string', function (): void {
    expect(activeUrlViolations(['x']))->toHaveCount(1);
});

it('returns on an absent value', function (): void {
    expect(activeUrlViolations(null))->toHaveCount(0)
        ->and(activeUrlViolations(''))->toHaveCount(0);
});
