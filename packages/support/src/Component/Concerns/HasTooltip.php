<?php

declare(strict_types=1);

namespace Sylament\Support\Component\Concerns;

use Closure;
use Twig\Markup;

trait HasTooltip
{
    protected string|Markup|Closure|null $tooltip = null;

    public function tooltip(string|Markup|Closure|null $tooltip): static
    {
        $this->tooltip = $tooltip;

        return $this;
    }

    public function getTooltip(): string|Markup|null
    {
        return $this->evaluate($this->tooltip);
    }

    public function hasTooltip(): bool
    {
        return $this->tooltip !== null;
    }
}
