<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

trait CanBeAside
{
    /**
     * @var bool|Closure():bool
     */
    protected bool|Closure $isAside = false;

    /**
     * @param bool|callable():bool $condition
     */
    public function aside(bool|callable $condition = true): static
    {
        $this->isAside = $this->evaluatable($condition);

        return $this;
    }

    public function isAside(): bool
    {
        return $this->evaluate($this->isAside);
    }
}
