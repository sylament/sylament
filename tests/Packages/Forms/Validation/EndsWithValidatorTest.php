<?php

declare(strict_types=1);

use Sylament\Forms\Validation\EndsWith;
use Symfony\Component\Validator\Validator\ValidatorInterface;

it('passes a value ending with any listed suffix, or with none of them when negated, and refuses a value that is no text either way', function (mixed $value, bool $match, ?string $message): void {
    $violations = $this::getContainer()->get(ValidatorInterface::class)->validate($value, new EndsWith(values: ['.com', '00'], match: $match));

    expect(array_map(static fn ($violation): string => (string) $violation->getMessage(), iterator_to_array($violations)))->toBe($message === null ? [] : [$message]);
})->with([
    'text ending with the first suffix' => ['example.com', true, null],
    'a number ending with a suffix' => [1200, true, null],
    'text ending with none' => ['example.org', true, 'This value must end with one of: .com, 00.'],
    'text ending with none, negated' => ['example.org', false, null],
    'text ending with one, negated' => ['example.com', false, 'This value must not end with any of: .com, 00.'],
    'a list' => [['example.com'], true, 'This value must end with one of: .com, 00.'],
    'a list, negated' => [['example.org'], false, 'This value must not end with any of: .com, 00.'],
    'a blank value' => ['', true, null],
]);
