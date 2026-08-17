<?php

declare(strict_types=1);

namespace Sylament\Schemas\State\Casts;

use BackedEnum;
use Sylament\Schemas\State\Casts\Contracts\StateCast;

use function Sylament\Support\blank;

class EnumStateCast implements StateCast
{
    /**
     * @param class-string<BackedEnum> $enum
     */
    public function __construct(
        protected string $enum,
    ) {
    }

    public function get(mixed $state): ?BackedEnum
    {
        if (blank($state)) {
            return null;
        }

        if ($state instanceof $this->enum) {
            return $state;
        }

        // Security: a legitimate raw state is a scalar spelling of a backing value
        // or an instance. A tampered payload delivering anything else has no case
        // to match, so fail closed by treating it as no selection.
        if (! is_int($state) && ! is_string($state)) {
            return null;
        }

        foreach ($this->enum::cases() as $case) {
            if ((string) $case->value === (string) $state) {
                return $case;
            }
        }

        return null;
    }

    public function set(mixed $state): mixed
    {
        if (! $state instanceof BackedEnum) {
            return $state;
        }

        return (string) $state->value;
    }
}
