<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;

/**
 * Ports Filament's actions `HasDropdown`. `dropdownTeleport()` and
 * `dropdownAutoPlacement()` are deferred with the dropdown features they
 * configure; `dropdownWidth()` takes a CSS length string — support has no
 * `Width` enum, and the value flows into a `--sy-dropdown-width` variable.
 */
trait HasDropdown
{
    protected bool|Closure $hasDropdown = true;

    protected string|Closure|null $dropdownPlacement = null;

    protected string|Closure|null $defaultDropdownPlacement = null;

    protected string|Closure|null $dropdownMaxHeight = null;

    protected int|Closure|null $dropdownOffset = null;

    protected string|Closure|null $dropdownWidth = null;

    protected bool|Closure $hasDropdownFlip = true;

    public function dropdown(bool|Closure $condition = true): static
    {
        $this->hasDropdown = $condition;

        return $this;
    }

    public function dropdownPlacement(string|Closure|null $placement): static
    {
        $this->dropdownPlacement = $placement;

        return $this;
    }

    public function defaultDropdownPlacement(string|Closure|null $placement): static
    {
        $this->defaultDropdownPlacement = $placement;

        return $this;
    }

    public function dropdownMaxHeight(string|Closure|null $height): static
    {
        $this->dropdownMaxHeight = $height;

        return $this;
    }

    public function dropdownOffset(int|Closure|null $offset): static
    {
        $this->dropdownOffset = $offset;

        return $this;
    }

    public function dropdownWidth(string|Closure|null $width): static
    {
        $this->dropdownWidth = $width;

        return $this;
    }

    public function dropdownFlip(bool|Closure $condition = true): static
    {
        $this->hasDropdownFlip = $condition;

        return $this;
    }

    public function hasDropdown(): bool
    {
        return (bool) $this->evaluate($this->hasDropdown);
    }

    public function getDropdownPlacement(): ?string
    {
        return $this->evaluate($this->dropdownPlacement) ?? $this->evaluate($this->defaultDropdownPlacement);
    }

    public function getDropdownMaxHeight(): ?string
    {
        return $this->evaluate($this->dropdownMaxHeight);
    }

    public function getDropdownOffset(): ?int
    {
        return $this->evaluate($this->dropdownOffset);
    }

    public function getDropdownWidth(): ?string
    {
        return $this->evaluate($this->dropdownWidth);
    }

    public function hasDropdownFlip(): bool
    {
        return (bool) $this->evaluate($this->hasDropdownFlip);
    }
}
