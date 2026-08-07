<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;

trait CanBeAutocapitalized
{
    protected bool|string|Closure|null $autocapitalize = null;

    public function autocapitalize(bool|string|Closure|null $autocapitalize = true): static
    {
        $this->autocapitalize = $autocapitalize;

        return $this;
    }

    public function getAutocapitalize(): ?string
    {
        $autocapitalize = $this->evaluate($this->autocapitalize);

        return match ($autocapitalize) {
            true => 'on',
            false => 'off',
            default => $autocapitalize,
        };
    }
}
