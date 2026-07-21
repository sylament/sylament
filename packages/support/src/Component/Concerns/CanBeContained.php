<?php

declare(strict_types=1);

namespace Sylament\Support\Component\Concerns;

use Closure;

trait CanBeContained
{
    /**
     * @var bool|Closure():bool
     */
    protected bool|Closure $isContained = true;

    protected bool $isContainedCache;

    /**
     * @param bool|callable():bool $condition
     */
    public function contained(bool|callable $condition = true): static
    {
        $this->isContained = $this->evaluatable($condition);

        return $this;
    }

    public function isContained(): bool
    {
        return $this->isContainedCache ??= $this->evaluate($this->isContained);
    }
}
