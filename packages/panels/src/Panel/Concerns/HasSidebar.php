<?php

declare(strict_types=1);

namespace Sylament\Panels\Panel\Concerns;

use Closure;

trait HasSidebar
{
    protected bool|Closure $hasCollapsibleNavigationGroups = true;

    public function collapsibleNavigationGroups(bool|Closure $condition = true): static
    {
        $this->hasCollapsibleNavigationGroups = $condition;

        return $this;
    }

    public function hasCollapsibleNavigationGroups(): bool
    {
        return (bool) $this->evaluate($this->hasCollapsibleNavigationGroups);
    }
}
