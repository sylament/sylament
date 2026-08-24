<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Validation;

use Sylament\Forms\Validation\MatchesField;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * @return ConstraintViolationListInterface<int, ConstraintViolationInterface>
 */
function matchesFieldViolations(mixed $value, MatchesField $constraint): ConstraintViolationListInterface
{
    return Validation::createValidator()->validate($value, $constraint);
}

it('accepts an identical partner', function (): void {
    expect(matchesFieldViolations('pw', new MatchesField(value: 'pw', other: 'Password')))->toHaveCount(0);
});

it('flags a different partner, naming it without printing it', function (): void {
    $violations = matchesFieldViolations('pw', new MatchesField(value: 'other', other: 'Password'));

    expect($violations)->toHaveCount(1)
        ->and($violations->get(0)->getMessage())->toBe('This value must match Password.');
});

it('treats a null partner as a value, not a skip — the case the stock classes cannot express', function (): void {
    expect(matchesFieldViolations('pw', new MatchesField(value: null, other: 'Password confirmation')))->toHaveCount(1)
        ->and(matchesFieldViolations('pw', new MatchesField(value: '', other: 'Password confirmation')))->toHaveCount(1);
});

it('compares strictly, so a wire string never matches a filled int', function (): void {
    expect(matchesFieldViolations('5', new MatchesField(value: 5, other: 'Count')))->toHaveCount(1);
});

it('inverts under match false', function (): void {
    $violations = matchesFieldViolations('pw', new MatchesField(value: 'pw', other: 'Password', match: false));

    expect($violations)->toHaveCount(1)
        ->and($violations->get(0)->getMessage())->toBe('This value must differ from Password.')
        ->and(matchesFieldViolations('pw', new MatchesField(value: 'other', other: 'Password', match: false)))->toHaveCount(0);
});

it('returns on a blank own value in both polarities', function (): void {
    expect(matchesFieldViolations(null, new MatchesField(value: 'pw', other: 'Password')))->toHaveCount(0)
        ->and(matchesFieldViolations('', new MatchesField(value: 'pw', other: 'Password')))->toHaveCount(0)
        ->and(matchesFieldViolations('', new MatchesField(value: '', other: 'Password', match: false)))->toHaveCount(0);
});

it('propagates custom messages', function (): void {
    $same = matchesFieldViolations('a', new MatchesField(value: 'b', other: 'B', message: 'Must equal.'));
    $different = matchesFieldViolations('a', new MatchesField(value: 'a', other: 'B', match: false, notMessage: 'Must differ.'));

    expect($same->get(0)->getMessage())->toBe('Must equal.')
        ->and($different->get(0)->getMessage())->toBe('Must differ.');
});
