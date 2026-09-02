<?php

declare(strict_types=1);

namespace Sylament\Panels\Panel\Concerns;

use Closure;
use Sylament\Support\Enums\Width;

trait HasMaxContentWidth
{
    protected Width|string|Closure|null $maxContentWidth = null;

    protected Width|string|Closure|null $simplePageMaxContentWidth = null;

    public function maxContentWidth(Width|string|Closure|null $width): static
    {
        $this->maxContentWidth = $width;

        return $this;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return $this->evaluate($this->maxContentWidth);
    }

    public function simplePageMaxContentWidth(Width|string|Closure|null $width): static
    {
        $this->simplePageMaxContentWidth = $width;

        return $this;
    }

    public function getSimplePageMaxContentWidth(): Width|string|null
    {
        return $this->evaluate($this->simplePageMaxContentWidth);
    }
}
