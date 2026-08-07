<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;

trait CanBeAutocompleted
{
    protected bool|string|Closure|null $autocomplete = null;

    public function autocomplete(bool|string|Closure|null $autocomplete = true): static
    {
        $this->autocomplete = $autocomplete;

        return $this;
    }

    public function getAutocomplete(): ?string
    {
        $autocomplete = $this->evaluate($this->autocomplete);

        return match ($autocomplete) {
            true => 'on',
            false => 'off',
            default => $autocomplete,
        };
    }
}
