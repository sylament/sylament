<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;
use Twig\Markup;

trait HasBadgeTooltip
{
    protected string|Markup|Closure|null $badgeTooltip = null;

    public function badgeTooltip(string|Markup|Closure|null $tooltip): static
    {
        $this->badgeTooltip = $tooltip;

        return $this;
    }

    public function getBadgeTooltip(?string $badge = null): string|Markup|null
    {
        return $this->evaluate($this->badgeTooltip, ['badge' => $badge]);
    }
}
