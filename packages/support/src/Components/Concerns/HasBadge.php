<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;
use Symfony\Contracts\Translation\TranslatableInterface;

trait HasBadge
{
    protected string|int|float|TranslatableInterface|Closure|null $badge = null;

    /**
     * @var string|array<int, string>|Closure|null
     */
    protected string|array|Closure|null $badgeColor = null;

    public function badge(string|int|float|TranslatableInterface|Closure|null $badge): static
    {
        $this->badge = $badge;

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

        if ($badge instanceof TranslatableInterface) {
            $badge = $this->translateText($badge);
        }

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
