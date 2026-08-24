<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;

trait HasReorderAnimationDuration
{
    protected int|Closure $reorderAnimationDuration = 300;

    public function reorderAnimationDuration(int|Closure $animation): static
    {
        $this->reorderAnimationDuration = $animation;

        return $this;
    }

    public function getReorderAnimationDuration(): int
    {
        return $this->evaluate($this->reorderAnimationDuration);
    }
}
