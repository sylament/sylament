<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use BackedEnum;
use Closure;
use Twig\Markup;

trait HasToggleIcons
{
    protected string|BackedEnum|Markup|Closure|null $offIcon = null;

    protected string|BackedEnum|Markup|Closure|null $onIcon = null;

    public function offIcon(string|BackedEnum|Markup|Closure|null $icon): static
    {
        $this->offIcon = $icon;

        return $this;
    }

    public function onIcon(string|BackedEnum|Markup|Closure|null $icon): static
    {
        $this->onIcon = $icon;

        return $this;
    }

    public function getOffIcon(): string|BackedEnum|Markup|null
    {
        return $this->evaluate($this->offIcon);
    }

    public function getOnIcon(): string|BackedEnum|Markup|null
    {
        return $this->evaluate($this->onIcon);
    }

    public function hasOffIcon(): bool
    {
        return $this->getOffIcon() !== null;
    }

    public function hasOnIcon(): bool
    {
        return $this->getOnIcon() !== null;
    }
}
