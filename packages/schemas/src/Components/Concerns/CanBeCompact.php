<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

trait CanBeCompact
{
    /**
     * @var bool|Closure():bool
     */
    protected bool|Closure $isCompact = false;

    /**
     * @param bool|callable():bool $condition
     */
    public function compact(bool|callable $condition = true): static
    {
        $this->isCompact = $this->evaluatable($condition);

        return $this;
    }

    public function isCompact(): bool
    {
        return $this->evaluate($this->isCompact);
    }
}
