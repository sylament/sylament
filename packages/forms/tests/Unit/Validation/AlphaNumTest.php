<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Validation;

use Sylament\Forms\Validation\AlphaNum;
use Symfony\Component\Validator\Validation;

it('accepts letters and numbers only', function (): void {
    expect(patternViolations('abc123', new AlphaNum()))->toBe(0)
        ->and(patternViolations('a-b', new AlphaNum()))->toBe(1)
        ->and(patternViolations('a b', new AlphaNum()))->toBe(1);
});

it('carries its own default message', function (): void {
    expect(Validation::createValidator()->validate('a-b', new AlphaNum())->get(0)->getMessage())
        ->toBe('This value may only contain letters and numbers.');
});
