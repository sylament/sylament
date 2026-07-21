<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Closure;

trait CanBeInline
{
    protected bool|Closure $isInline = false;

    public function inline(bool|Closure $condition = true): static
    {
        $this->isInline = $condition;

        return $this;
    }

    public function isInline(): bool
    {
        return $this->evaluate($this->isInline);
    }
}
