<?php

declare(strict_types=1);

namespace Sylament\Schemas\State\Casts;

use Sylament\Schemas\State\Casts\Contracts\StateCast;

class StripCharactersStateCast implements StateCast
{
    /**
     * @param list<string> $characters
     */
    public function __construct(
        protected readonly array $characters = [],
    ) {
    }

    public function get(mixed $state): mixed
    {
        return $this->strip($state);
    }

    public function set(mixed $state): mixed
    {
        return $this->strip($state);
    }

    private function strip(mixed $state): mixed
    {
        if ($this->characters === []) {
            return $state;
        }

        if (is_array($state)) {
            return array_map(fn (mixed $value): mixed => is_string($value) ? str_replace($this->characters, '', $value) : $value, $state);
        }

        if (! is_string($state)) {
            return $state;
        }

        return str_replace($this->characters, '', $state);
    }
}
