<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;

trait HasToggleColors
{
    /**
     * @var string|array<string>|Closure|null
     */
    protected string|array|Closure|null $offColor = null;

    /**
     * @var string|array<string>|Closure|null
     */
    protected string|array|Closure|null $onColor = null;

    /**
     * @param string|array<string>|Closure|null $color
     */
    public function offColor(string|array|Closure|null $color): static
    {
        $this->offColor = $color;

        return $this;
    }

    /**
     * @param string|array<string>|Closure|null $color
     */
    public function onColor(string|array|Closure|null $color): static
    {
        $this->onColor = $color;

        return $this;
    }

    /**
     * @return string|array<string>|null
     */
    public function getOffColor(): string|array|null
    {
        return $this->evaluate($this->offColor);
    }

    /**
     * @return string|array<string>|null
     */
    public function getOnColor(): string|array|null
    {
        return $this->evaluate($this->onColor);
    }
}
