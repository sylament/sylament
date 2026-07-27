<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;

trait CanBeContained
{
    protected bool|Closure $isContained = true;

    protected bool $isContainedCache;

    public function contained(bool|Closure $condition = true): static
    {
        $this->isContained = $condition;

        return $this;
    }

    public function isContained(): bool
    {
        return $this->isContainedCache ??= $this->evaluate($this->isContained);
    }
}
