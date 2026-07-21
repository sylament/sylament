<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

trait HasContentBefore
{
    protected bool|Closure $hasContentBefore = false;

    public function contentBefore(bool|Closure $condition = true): static
    {
        $this->hasContentBefore = $condition;

        return $this;
    }

    public function hasContentBefore(): bool
    {
        return $this->evaluate($this->hasContentBefore);
    }
}
