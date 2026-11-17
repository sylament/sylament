<?php

declare(strict_types=1);

use Sylament\Forms\Validation\StartsWith;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

it('refuses an empty value list at construction', function (): void {
    expect(fn (): StartsWith => new StartsWith(values: []))
        ->toThrow(InvalidArgumentException::class, '[Sylament\Forms\Validation\StartsWith] needs at least one value to compare against.');
});
