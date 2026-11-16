<?php

declare(strict_types=1);

use Sylament\Forms\Validation\RequiredValue;
use Sylament\Schemas\Validation\PresenceGate;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\DivisibleBy;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\IsFalse;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;

it('keeps every constraint for a present value and only the implicit ones, in order, for a blank string', function (): void {
    $constraints = [
        new Email(),
        $notBlank = new NotBlank(),
        new GreaterThanOrEqual(1),
        $required = new RequiredValue(),
        new Type('numeric'),
        $isTrue = new IsTrue(),
        new Regex('/^\d+$/'),
        $notNull = new NotNull(),
        new Length(min: 3),
        $isFalse = new IsFalse(),
        new Choice(choices: ['a', 'b']),
        new DivisibleBy(2),
        new Count(min: 1),
        new All(new NotBlank()),
    ];

    expect(PresenceGate::apply('', $constraints))->toBe([$notBlank, $required, $isTrue, $notNull, $isFalse])
        ->and(PresenceGate::apply('7', $constraints))->toBe($constraints);
});

it('gates a string that trims to nothing, multibyte spaces included, and nothing else', function (mixed $value, bool $gated): void {
    $constraints = [$required = new RequiredValue(), new Length(min: 3)];

    expect(PresenceGate::apply($value, $constraints))->toBe($gated ? [$required] : $constraints);
})->with([
    'an empty string' => ['', true],
    'whitespace' => [" \t\n", true],
    'a non-breaking space' => ["\u{00A0}", true],
    'a string reading zero' => ['0', false],
    'null' => [null, false],
    'an empty array' => [[], false],
]);

it('keeps a subclass of an implicit constraint', function (): void {
    $handle = new class () extends NotBlank {};

    expect(PresenceGate::apply('', [$handle, new Email()]))->toBe([$handle]);
});
