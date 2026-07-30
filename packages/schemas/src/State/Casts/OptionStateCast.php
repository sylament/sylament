<?php

declare(strict_types=1);

namespace Sylament\Schemas\State\Casts;

use BackedEnum;
use Stringable;
use Sylament\Schemas\State\Casts\Contracts\StateCast;

use function Sylament\Support\blank;

class OptionStateCast implements StateCast
{
    public function __construct(
        protected bool $isNullable = true,
    ) {
    }

    public function get(mixed $state): string|int|null
    {
        if ($this->isNullable && blank($state)) {
            return null;
        }

        // Security: a legitimate single-option state is a scalar, a BackedEnum or null.
        // A tampered payload delivering anything else would stringify below to a bogus
        // option value ("Array"), so fail closed by treating it as no selection.
        if (! is_scalar($state) && ! $state instanceof BackedEnum && ! $state instanceof Stringable) {
            return null;
        }

        if ($state instanceof BackedEnum) {
            $state = $state->value;
        }

        if ($state instanceof Stringable) {
            $state = (string) $state;
        }

        if (is_int($state)) {
            return $state;
        }

        if (is_string($state) && ctype_digit($state) && ($state === '0' || ! str_starts_with($state, '0'))) {
            $max = (string) PHP_INT_MAX;

            if (mb_strlen($state) > mb_strlen($max) || (mb_strlen($state) === mb_strlen($max) && strcmp($state, $max) > 0)) {
                return $state;
            }

            return (int) $state;
        }

        return (string) $state;
    }

    public function set(mixed $state): ?string
    {
        if ($this->isNullable && blank($state)) {
            return null;
        }

        // Security: mirror get() — a non-scalar payload would stringify to a bogus
        // option value, so fail closed as no selection.
        if (! is_scalar($state) && ! $state instanceof BackedEnum && ! $state instanceof Stringable) {
            return null;
        }

        if ($state instanceof BackedEnum) {
            $state = $state->value;
        }

        return (string) $state;
    }
}
