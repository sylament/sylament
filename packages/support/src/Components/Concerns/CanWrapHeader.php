<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;

trait CanWrapHeader
{
    protected bool|Closure $canHeaderWrap = false;

    public function wrapHeader(bool|Closure $condition = true): static
    {
        $this->canHeaderWrap = $condition;

        return $this;
    }

    public function canHeaderWrap(): bool
    {
        return $this->evaluate($this->canHeaderWrap);
    }
}
