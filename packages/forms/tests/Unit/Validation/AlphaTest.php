<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Validation;

use Sylament\Forms\Validation\Alpha;
use Sylament\Forms\Validation\AlphaDash;
use Sylament\Forms\Validation\AlphaNum;
use Sylament\Forms\Validation\Ascii;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\RegexValidator;
use Symfony\Component\Validator\Validation;

function patternViolations(mixed $value, Constraint $constraint): int
{
    return count(Validation::createValidator()->validate($value, $constraint));
}

it('accepts unicode letters and refuses anything else', function (): void {
    expect(patternViolations('Ünïcode', new Alpha()))->toBe(0)
        ->and(patternViolations('abc', new Alpha()))->toBe(0)
        ->and(patternViolations('abc1', new Alpha()))->toBe(1)
        ->and(patternViolations('a b', new Alpha()))->toBe(1);
});

it('refuses a trailing newline, which the \\A…\\z anchors are for', function (): void {
    expect(patternViolations("abc\n", new Alpha()))->toBe(1);
});

it('returns on a blank value', function (): void {
    expect(patternViolations('', new Alpha()))->toBe(0)
        ->and(patternViolations(null, new Alpha()))->toBe(0);
});

it('carries a readable default message and propagates a custom one', function (): void {
    $violations = Validation::createValidator()->validate('1', new Alpha());
    $custom = Validation::createValidator()->validate('1', new Alpha(message: 'Letters only.'));

    expect($violations->get(0)->getMessage())->toBe('This value may only contain letters.')
        ->and($custom->get(0)->getMessage())->toBe('Letters only.');
});

it("validates each subclass through Symfony's own RegexValidator", function (): void {
    expect(new Alpha()->validatedBy())->toBe(RegexValidator::class)
        ->and(new AlphaDash()->validatedBy())->toBe(RegexValidator::class)
        ->and(new AlphaNum()->validatedBy())->toBe(RegexValidator::class)
        ->and(new Ascii()->validatedBy())->toBe(RegexValidator::class);
});

it('inherits getHtmlPattern() untouched, which answers null for the unicode arms', function (): void {
    // Regex::getHtmlPattern() bails when the pattern's first and last characters differ,
    // which the `u` modifier guarantees for the three unicode classes.
    expect(new Alpha()->getHtmlPattern())->toBeNull()
        ->and(new AlphaDash()->getHtmlPattern())->toBeNull()
        ->and(new AlphaNum()->getHtmlPattern())->toBeNull()
        ->and(new Ascii()->getHtmlPattern())->toBe('.*\A[\x09\x0A\x0D\x10\x13\x20-\x7E]*\z.*');
});
