<?php

declare(strict_types=1);

use Sylament\Forms\Validation\StartsWith;
use Symfony\Component\Validator\Validator\ValidatorInterface;

it('passes a value starting with any listed prefix, or with none of them when negated, and refuses a value that is no text either way', function (mixed $value, bool $match, ?string $message): void {
    $violations = $this::getContainer()->get(ValidatorInterface::class)->validate($value, new StartsWith(values: ['ab', '12'], match: $match));

    expect(array_map(static fn ($violation): string => (string) $violation->getMessage(), iterator_to_array($violations)))->toBe($message === null ? [] : [$message]);
})->with([
    'text starting with the second prefix' => ['abc', true, null],
    'a number starting with a prefix' => [123, true, null],
    'text starting with none' => ['xyz', true, 'This value must start with one of: ab, 12.'],
    'text starting with none, negated' => ['xyz', false, null],
    'text starting with one, negated' => ['12x', false, 'This value must not start with any of: ab, 12.'],
    'a list' => [['abc'], true, 'This value must start with one of: ab, 12.'],
    'a list, negated' => [['xyz'], false, 'This value must not start with any of: ab, 12.'],
    'a blank value' => ['', true, null],
]);
