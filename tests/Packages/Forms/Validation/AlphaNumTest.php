<?php

declare(strict_types=1);

use Sylament\Forms\Validation\AlphaNum;
use Symfony\Component\Validator\Validator\ValidatorInterface;

it('takes letters, marks and digits of any script, and refuses a dash or a trailing newline', function (string $value, bool $passes): void {
    $violations = $this::getContainer()->get(ValidatorInterface::class)->validate($value, new AlphaNum());

    expect(array_map(static fn ($violation): string => (string) $violation->getMessage(), iterator_to_array($violations)))->toBe($passes ? [] : ['This value may only contain letters and numbers.']);
})->with([
    'letters and digits' => ['Ada1815', true],
    'multibyte letters and digits' => ['Žofia٣', true],
    'a dash' => ['Ada-1815', false],
    'a trailing newline' => ["Ada\n", false],
]);
