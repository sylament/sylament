<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;

/**
 * Ports Filament's actions `HasGroupedIcon`, narrowed to `string` icons —
 * no `BackedEnum`/`Markup` icon values yet, the same narrowing `CanOpenModal`'s
 * `modalIcon` applies.
 */
trait HasGroupedIcon
{
    protected string|Closure|null $groupedIcon = null;

    public function groupedIcon(string|Closure|null $icon): static
    {
        $this->groupedIcon = $icon;

        return $this;
    }

    public function getGroupedIcon(): ?string
    {
        return $this->evaluate($this->groupedIcon);
    }

    public function hasGroupedIcon(): bool
    {
        return $this->groupedIcon !== null;
    }
}
