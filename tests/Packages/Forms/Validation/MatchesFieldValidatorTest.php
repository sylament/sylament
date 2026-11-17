<?php

declare(strict_types=1);

use Sylament\Forms\Validation\MatchesField;
use Symfony\Component\Validator\Validator\ValidatorInterface;

it("compares a value with its partner's strictly, naming the partner and never the value it compared", function (mixed $value, mixed $partner, bool $match, ?string $message): void {
    $violations = $this::getContainer()->get(ValidatorInterface::class)->validate($value, new MatchesField(value: $partner, other: 'Repeat password', match: $match));

    expect(array_map(static fn ($violation): string => (string) $violation->getMessage(), iterator_to_array($violations)))->toBe($message === null ? [] : [$message]);
})->with([
    'the same value' => ['s3cret', 's3cret', true, null],
    'another value' => ['s3cret', 'secret', true, 'This value must match Repeat password.'],
    'an empty partner' => ['s3cret', null, true, 'This value must match Repeat password.'],
    'a number its partner holds as text' => [1, '1', true, 'This value must match Repeat password.'],
    'a blank own value' => ['', 's3cret', true, null],
    'a value it must differ from' => ['s3cret', 's3cret', false, 'This value must differ from Repeat password.'],
    'a value that differs' => ['s3cret', 'secret', false, null],
]);
