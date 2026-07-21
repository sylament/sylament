<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Twig\Markup;

trait HasLabel
{
    protected bool|Closure $isLabelHidden = false;

    protected string|Markup|Closure|null $label = null;

    public function hiddenLabel(bool|Closure $condition = true): static
    {
        $this->isLabelHidden = $condition;

        return $this;
    }

    public function isLabelHidden(): bool
    {
        return $this->evaluate($this->isLabelHidden);
    }

    public function label(string|Markup|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string|Markup|null
    {
        return $this->evaluate($this->label);
    }

    public function hasCustomLabel(): bool
    {
        return $this->label !== null;
    }
}
