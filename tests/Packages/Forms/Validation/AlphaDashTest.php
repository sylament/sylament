<?php

declare(strict_types=1);

use Sylament\Forms\Validation\AlphaDash;
use Symfony\Component\Validator\Validator\ValidatorInterface;

it('takes letters, marks, digits, dashes and underscores, and refuses a space or a trailing newline', function (string $value, bool $passes): void {
    $violations = $this::getContainer()->get(ValidatorInterface::class)->validate($value, new AlphaDash());

    expect(array_map(static fn ($violation): string => (string) $violation->getMessage(), iterator_to_array($violations)))->toBe($passes ? [] : ['This value may only contain letters, numbers, dashes and underscores.']);
})->with([
    'a slug' => ['ada_lovelace-1815', true],
    'multibyte letters and digits' => ['Žofia-٣', true],
    'a space' => ['ada lovelace', false],
    'a trailing newline' => ["ada\n", false],
]);
