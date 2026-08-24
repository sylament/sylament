<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Validation;

use Sylament\Forms\Validation\AlphaDash;
use Symfony\Component\Validator\Validation;

it('accepts letters, numbers, dashes and underscores', function (): void {
    expect(patternViolations('a-b_1', new AlphaDash()))->toBe(0)
        ->and(patternViolations('Ünïcode-1', new AlphaDash()))->toBe(0)
        ->and(patternViolations('a b', new AlphaDash()))->toBe(1)
        ->and(patternViolations("a\n", new AlphaDash()))->toBe(1);
});

it('carries its own default message', function (): void {
    expect(Validation::createValidator()->validate('a b', new AlphaDash())->get(0)->getMessage())
        ->toBe('This value may only contain letters, numbers, dashes and underscores.');
});
