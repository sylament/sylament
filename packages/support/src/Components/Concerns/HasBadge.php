<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;

trait HasBadge
{
    protected string|Closure|null $badge = null;

    /**
     * @var string|array<int, string>|Closure|null
     */
    protected string|array|Closure|null $badgeColor = null;

    public function badge(string|int|float|Closure|null $badge): static
    {
        $this->badge = ($badge instanceof Closure || $badge === null) ? $badge : (string) $badge;

        return $this;
    }

    /**
     * @param string|array<int, string>|Closure|null $color
     */
    public function badgeColor(string|array|Closure|null $color): static
    {
        $this->badgeColor = $color;

        return $this;
    }

    public function getBadge(): ?string
    {
        $badge = $this->evaluate($this->badge);

        return $badge === null ? null : (string) $badge;
    }

    /**
     * @return string|array<int, string>|null
     */
    public function getBadgeColor(?string $badge = null): string|array|null
    {
        return $this->evaluate($this->badgeColor, ['badge' => $badge]);
    }

    public function hasBadge(): bool
    {
        return $this->badge !== null;
    }
}
