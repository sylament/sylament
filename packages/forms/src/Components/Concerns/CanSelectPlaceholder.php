<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;

trait CanSelectPlaceholder
{
    protected bool|Closure $canSelectPlaceholder = true;

    public function selectablePlaceholder(bool|Closure $condition = true): static
    {
        $this->canSelectPlaceholder = $condition;

        return $this;
    }

    public function canSelectPlaceholder(): bool
    {
        return (bool) $this->evaluate($this->canSelectPlaceholder);
    }
}
