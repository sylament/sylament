<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

trait CanBeDivided
{
    /**
     * @var bool|Closure():bool
     */
    protected bool|Closure $isDivided = false;

    /**
     * @param bool|callable():bool $condition
     */
    public function divided(bool|callable $condition = true): static
    {
        $this->isDivided = $this->evaluatable($condition);

        return $this;
    }

    public function isDivided(): bool
    {
        return $this->evaluate($this->isDivided);
    }
}
