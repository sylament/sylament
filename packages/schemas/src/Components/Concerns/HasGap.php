<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

trait HasGap
{
    protected bool|Closure|null $hasGap = null;

    protected bool|Closure|null $isDense = null;

    public function gap(bool|Closure|null $condition = true): static
    {
        $this->hasGap = $condition;

        return $this;
    }

    public function hasGap(): bool
    {
        return $this->evaluate($this->hasGap) ?? $this->getContainer()->hasGap();
    }

    public function dense(bool|Closure|null $condition = true): static
    {
        $this->isDense = $condition;

        return $this;
    }

    public function isDense(): bool
    {
        return $this->evaluate($this->isDense) ?? $this->getContainer()->isDense();
    }
}
