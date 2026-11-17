<?php

declare(strict_types=1);

use Sylament\Forms\Validation\Alpha;
use Symfony\Component\Validator\Validator\ValidatorInterface;

it('takes letters and combining marks of any script, and refuses a digit, a dash or a trailing newline', function (string $value, bool $passes): void {
    $violations = $this::getContainer()->get(ValidatorInterface::class)->validate($value, new Alpha());

    expect(array_map(static fn ($violation): string => (string) $violation->getMessage(), iterator_to_array($violations)))->toBe($passes ? [] : ['This value may only contain letters.']);
})->with([
    'ascii letters' => ['Ada', true],
    'multibyte letters' => ['Žofia', true],
    'a letter and a combining mark' => ["e\u{0301}", true],
    'a digit' => ['Ada1', false],
    'a dash' => ['Ada-Lovelace', false],
    'a trailing newline' => ["Ada\n", false],
]);
