<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

trait CanBeAside
{
    /**
     * @var bool|Closure():bool|null
     */
    protected bool|Closure|null $isAside = null;

    /**
     * @param bool|callable():bool|null $condition
     */
    public function aside(bool|callable|null $condition = true): static
    {
        $this->isAside = $this->evaluatable($condition);

        return $this;
    }

    public function isAside(): bool
    {
        return $this->evaluate($this->isAside) ?? false;
    }
}
