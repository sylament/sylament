<?php

declare(strict_types=1);

use Sylament\Forms\Validation\Ascii;
use Symfony\Component\Validator\Validator\ValidatorInterface;

it("takes what Laravel's ascii rule takes, the control characters \\x10 and \\x13 included, and refuses NUL and a multibyte letter", function (string $value, bool $passes): void {
    $violations = $this::getContainer()->get(ValidatorInterface::class)->validate($value, new Ascii());

    expect(array_map(static fn ($violation): string => (string) $violation->getMessage(), iterator_to_array($violations)))->toBe($passes ? [] : ['This value may only contain ASCII characters.']);
})->with([
    'printable text, a tab and a newline' => ["Ada Lovelace\t1815\n", true],
    'the control characters \x10 and \x13' => ["a\x10b\x13c", true],
    'NUL' => ["a\x00b", false],
    'a multibyte letter' => ['Žofia', false],
]);
