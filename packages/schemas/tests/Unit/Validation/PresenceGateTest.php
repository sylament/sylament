<?php

declare(strict_types=1);

use Sylament\Schemas\Tests\Fixtures\Validation\ImplicitRegex;
use Sylament\Schemas\Validation\PresenceGate;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\IsFalse;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validation;

it('gates the empty string down to the implicit constraints', function (): void {
    $notBlank = new NotBlank();

    expect(PresenceGate::apply('', [$notBlank, new Length(min: 3), new Type('numeric')]))
        ->toBe([$notBlank]);
});

it('gates a whitespace-only string', function (): void {
    expect(PresenceGate::apply("  \t\n ", [new Length(min: 3)]))->toBe([]);
});

it('gates a string of non-breaking space, which byte trim would keep', function (): void {
    expect(PresenceGate::apply("\u{00A0}", [new Length(min: 3)]))->toBe([]);
});

it('passes null through unchanged', function (): void {
    $constraints = [new Length(min: 3), new GreaterThanOrEqual(1)];

    expect(PresenceGate::apply(null, $constraints))->toBe($constraints);
});

it('passes a non-blank string through unchanged', function (): void {
    $constraints = [new Length(min: 3), new GreaterThanOrEqual(1)];

    expect(PresenceGate::apply('abc', $constraints))->toBe($constraints);
});

it('passes a non-string value through unchanged', function (mixed $value): void {
    $constraints = [new Length(min: 3), new GreaterThanOrEqual(1)];

    expect(PresenceGate::apply($value, $constraints))->toBe($constraints);
})->with([
    'integer zero' => 0,
    'float zero' => 0.0,
    'false' => false,
    'true' => true,
    'empty array' => [[[]]],
    'empty countable' => new ArrayObject(),
]);

it('keeps each implicit constraint against a blank string', function (object $constraint): void {
    expect(PresenceGate::apply('', [$constraint]))->toBe([$constraint]);
})->with([
    'NotBlank' => fn (): NotBlank => new NotBlank(),
    'NotNull' => fn (): NotNull => new NotNull(),
    'IsTrue' => fn (): IsTrue => new IsTrue(),
    'IsFalse' => fn (): IsFalse => new IsFalse(),
]);

it('keeps a constraint that opts in through ImplicitConstraint', function (): void {
    $implicit = new ImplicitRegex('/^\d+$/');

    expect(PresenceGate::apply('', [$implicit, new Length(min: 3)]))->toBe([$implicit]);
});

it('reindexes the surviving constraints into a list', function (): void {
    $notBlank = new NotBlank();

    expect(PresenceGate::apply('', [new Length(min: 3), $notBlank]))->toBe([$notBlank]);
});

it('hands the validator an empty constraint list without error', function (): void {
    $validator = Validation::createValidator();

    expect($validator->validate('', PresenceGate::apply('', [new Length(min: 3)])))->toHaveCount(0);
});
