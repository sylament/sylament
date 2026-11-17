<?php

declare(strict_types=1);

use Sylament\Forms\Validation\EndsWith;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

it('refuses an empty value list at construction', function (): void {
    expect(fn (): EndsWith => new EndsWith(values: []))
        ->toThrow(InvalidArgumentException::class, '[Sylament\Forms\Validation\EndsWith] needs at least one value to compare against.');
});
