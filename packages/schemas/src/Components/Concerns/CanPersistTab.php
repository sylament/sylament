<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

trait CanPersistTab
{
    protected bool|Closure $isTabPersisted = false;

    public function persistTab(bool|Closure $condition = true): static
    {
        $this->isTabPersisted = $condition;

        return $this;
    }

    public function isTabPersisted(): bool
    {
        return $this->evaluate($this->isTabPersisted);
    }
}
