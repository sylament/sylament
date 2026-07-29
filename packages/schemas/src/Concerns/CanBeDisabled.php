<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Closure;

trait CanBeDisabled
{
    protected bool|Closure $isDisabled = false;

    public function disabled(bool|Closure $condition = true): static
    {
        $this->isDisabled = $condition;

        return $this;
    }

    public function isDisabled(): bool
    {
        if ($this->evaluate($this->isDisabled)) {
            return true;
        }

        return (bool) $this->getParentComponent()?->isDisabled();
    }

    public function isEnabled(): bool
    {
        return ! $this->isDisabled();
    }
}
