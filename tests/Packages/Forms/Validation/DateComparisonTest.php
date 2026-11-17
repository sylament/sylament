<?php

declare(strict_types=1);

use Sylament\Forms\Validation\DateComparison;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

it('refuses an operator it does not know, naming the four it does', function (): void {
    expect(fn (): DateComparison => new DateComparison('on', new DateTimeImmutable('2026-01-01')))
        ->toThrow(InvalidArgumentException::class, 'The date comparison operator [on] is unknown. Use one of: [after], [after_or_equal], [before], [before_or_equal].');
});
