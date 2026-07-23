<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use BackedEnum;
use Closure;
use Twig\Markup;

use function Sylament\Support\filled;

trait HasHint
{
    protected string|Markup|Closure|null $hint = null;

    /**
     * @var string|array<string>|Closure|null
     */
    protected string|array|Closure|null $hintColor = null;

    protected string|BackedEnum|Markup|Closure|null $hintIcon = null;

    protected string|Closure|null $hintIconTooltip = null;

    public function hint(string|Markup|Closure|null $hint): static
    {
        $this->hint = $hint;

        return $this;
    }

    /**
     * @param string|array<string>|Closure|null $color
     */
    public function hintColor(string|array|Closure|null $color): static
    {
        $this->hintColor = $color;

        return $this;
    }

    public function hintIcon(string|BackedEnum|Markup|Closure|null $icon, string|Closure|null $tooltip = null): static
    {
        $this->hintIcon = $icon;

        if (func_num_args() >= 2) {
            $this->hintIconTooltip($tooltip);
        }

        return $this;
    }

    public function hintIconTooltip(string|Closure|null $tooltip): static
    {
        $this->hintIconTooltip = $tooltip;

        return $this;
    }

    public function hasHint(): bool
    {
        return filled($this->hint);
    }

    public function getHint(): string|Markup|null
    {
        return $this->evaluate($this->hint);
    }

    /**
     * @return string|array<string>|null
     */
    public function getHintColor(): string|array|null
    {
        return $this->evaluate($this->hintColor);
    }

    public function hasHintIcon(): bool
    {
        return filled($this->hintIcon);
    }

    public function getHintIcon(): string|BackedEnum|Markup|null
    {
        return $this->evaluate($this->hintIcon);
    }

    public function getHintIconTooltip(): ?string
    {
        return $this->evaluate($this->hintIconTooltip);
    }
}
