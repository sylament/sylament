<?php

declare(strict_types=1);

use Sylament\Forms\Validation\Distinct;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

it('refuses an unknown mode at construction, naming the modes it knows', function (): void {
    expect(fn (): Distinct => new Distinct(mode: 'nope'))
        ->toThrow(InvalidArgumentException::class, 'The distinct comparison mode [nope] is unknown. Use one of: [scalar], [array], [boolean].');
});
