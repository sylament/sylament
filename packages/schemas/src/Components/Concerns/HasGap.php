<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Sylament\Schemas\Schema;

trait HasGap
{
    protected bool|Closure|null $hasGap = null;

    protected bool|Closure|null $isDense = null;

    public function gap(bool|Closure|null $hasGap = true): static
    {
        $this->hasGap = $hasGap;

        return $this;
    }

    public function hasGap(): bool
    {
        $hasGap = $this->evaluate($this->hasGap);
        if ($hasGap !== null) {
            return $hasGap;
        }

        if ($this instanceof Schema) {
            return $this->getParentComponent()?->hasGap() ?? true;
        }

        return $this->getContainer()->hasGap();
    }

    public function dense(bool|Closure|null $isDense = true): static
    {
        $this->isDense = $isDense;

        return $this;
    }

    public function isDense(): bool
    {
        $isDense = $this->evaluate($this->isDense);
        if ($isDense !== null) {
            return $isDense;
        }

        if ($this instanceof Schema) {
            return $this->getParentComponent()?->isDense() ?? false;
        }

        return $this->getContainer()->isDense();
    }
}
