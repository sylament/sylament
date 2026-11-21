<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Markup;

trait HasTooltip
{
    protected string|Markup|TranslatableInterface|Closure|null $tooltip = null;

    public function tooltip(string|Markup|TranslatableInterface|Closure|null $tooltip): static
    {
        $this->tooltip = $tooltip;

        return $this;
    }

    public function getTooltip(): string|Markup|null
    {
        return $this->translateText($this->evaluate($this->tooltip));
    }

    public function hasTooltip(): bool
    {
        return $this->tooltip !== null;
    }
}
