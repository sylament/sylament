<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

trait CanBeSecondary
{
    protected bool|Closure $isSecondary = false;

    public function secondary(bool|Closure $condition = true): static
    {
        $this->isSecondary = $condition;

        return $this;
    }

    public function isSecondary(): bool
    {
        return $this->evaluate($this->isSecondary);
    }
}
