<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Sylament\Schemas\Schema;

trait HasGap
{
    /**
     * @var bool|Closure():bool|null
     */
    protected bool|Closure|null $hasGap = null;

    /**
     * @var bool|Closure():bool|null
     */
    protected bool|Closure|null $isDense = null;

    /**
     * @param bool|callable():bool|null $hasGap
     */
    public function gap(bool|callable|null $hasGap = true): static
    {
        $this->hasGap = $this->evaluatable($hasGap);

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

    /**
     * @param bool|callable():bool|null $isDense
     */
    public function dense(bool|callable|null $isDense = true): static
    {
        $this->isDense = $this->evaluatable($isDense);

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
