<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use BackedEnum;
use Closure;
use Twig\Markup;

use function Sylament\Support\filled;

trait HasIcon
{
    protected string|BackedEnum|Markup|Closure|false|null $icon = null;

    public function icon(string|BackedEnum|Markup|Closure|null $icon): static
    {
        $this->icon = filled($icon) ? $icon : false;

        return $this;
    }

    public function getIcon(string|BackedEnum|Markup|null $default = null): string|BackedEnum|Markup|null
    {
        $icon = $this->evaluate($this->icon);

        if ($icon === false) {
            return null;
        }

        return $icon ?? $default;
    }

    public function hasIcon(): bool
    {
        return $this->icon !== null && $this->icon !== false;
    }
}
