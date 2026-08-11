<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;

trait HasDropdown
{
    protected bool|Closure $hasDropdown = true;

    protected string|Closure|null $dropdownPlacement = null;

    protected string|Closure|null $defaultDropdownPlacement = null;

    protected string|Closure|null $dropdownMaxHeight = null;

    protected int|Closure|null $dropdownOffset = null;

    protected string|Closure|null $dropdownWidth = null;

    protected bool|Closure $hasDropdownFlip = true;

    protected bool|Closure|null $hasDropdownTeleport = null;

    protected bool|Closure|null $hasDefaultDropdownTeleport = null;

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

    public function dropdownTeleport(bool|Closure|null $condition = true): static
    {
        $this->hasDropdownTeleport = $condition;

        return $this;
    }

    public function defaultDropdownTeleport(bool|Closure|null $condition = true): static
    {
        $this->hasDefaultDropdownTeleport = $condition;

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

    public function hasDropdownTeleport(): bool
    {
        return (bool) ($this->evaluate($this->hasDropdownTeleport) ?? $this->evaluate($this->hasDefaultDropdownTeleport));
    }
}
