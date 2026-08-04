<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;

trait CanLimitItemsLength
{
    protected int|Closure|null $maxItems = null;

    protected int|Closure|null $minItems = null;

    public function maxItems(int|Closure|null $count): static
    {
        $this->maxItems = $count;

        return $this;
    }

    public function minItems(int|Closure|null $count): static
    {
        $this->minItems = $count;

        return $this;
    }

    public function getMaxItems(): ?int
    {
        return $this->evaluate($this->maxItems);
    }

    public function getMinItems(): ?int
    {
        return $this->evaluate($this->minItems);
    }

    public function getItemsCount(): int
    {
        $state = $this->getRawState();

        return is_array($state) ? count($state) : 0;
    }
}
