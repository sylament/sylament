<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Validation;

use Sylament\Forms\Validation\Ascii;
use Symfony\Component\Validator\Validation;

it('accepts printable ascii and a tab', function (): void {
    expect(patternViolations("abc\t", new Ascii()))->toBe(0)
        ->and(patternViolations('', new Ascii()))->toBe(0);
});

it('refuses a non-ascii character', function (): void {
    expect(patternViolations('é', new Ascii()))->toBe(1);
});

it('reproduces the upstream class quirks: NUL is refused, x10 and x13 are not', function (): void {
    expect(patternViolations("\0", new Ascii()))->toBe(1)
        ->and(patternViolations("\x10", new Ascii()))->toBe(0)
        ->and(patternViolations("\x13", new Ascii()))->toBe(0);
});

it('carries its own default message', function (): void {
    expect(Validation::createValidator()->validate('é', new Ascii())->get(0)->getMessage())
        ->toBe('This value may only contain ASCII characters.');
});
