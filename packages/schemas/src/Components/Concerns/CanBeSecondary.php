<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

trait CanBeSecondary
{
    /**
     * @var bool|Closure():bool
     */
    protected bool|Closure $isSecondary = false;

    /**
     * @param bool|callable():bool $condition
     */
    public function secondary(bool|callable $condition = true): static
    {
        $this->isSecondary = $this->evaluatable($condition);

        return $this;
    }

    public function isSecondary(): bool
    {
        return $this->evaluate($this->isSecondary);
    }
}
