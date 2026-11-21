<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Markup;

trait HasBadgeTooltip
{
    protected string|Markup|TranslatableInterface|Closure|null $badgeTooltip = null;

    public function badgeTooltip(string|Markup|TranslatableInterface|Closure|null $tooltip): static
    {
        $this->badgeTooltip = $tooltip;

        return $this;
    }

    public function getBadgeTooltip(?string $badge = null): string|Markup|null
    {
        return $this->translateText($this->evaluate($this->badgeTooltip, ['badge' => $badge]));
    }
}
