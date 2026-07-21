<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

trait HasContentBefore
{
    /**
     * @var bool|Closure():bool
     */
    protected bool|Closure $hasContentBefore = false;

    /**
     * @param bool|callable():bool $condition
     */
    public function contentBefore(bool|callable $condition = true): static
    {
        $this->hasContentBefore = $this->evaluatable($condition);

        return $this;
    }

    public function hasContentBefore(): bool
    {
        return $this->evaluate($this->hasContentBefore);
    }
}
