<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

trait CanBeAside
{
    protected bool|Closure|null $isAside = null;

    public function aside(bool|Closure|null $condition = true): static
    {
        $this->isAside = $condition;

        return $this;
    }

    public function isAside(): bool
    {
        return $this->evaluate($this->isAside) ?? false;
    }
}
