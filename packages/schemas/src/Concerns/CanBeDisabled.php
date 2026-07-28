<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Closure;

/**
 * Container-level disabled state. Ports Filament's identically-named schema
 * concern verbatim: a schema (e.g. a wizard step, a nested form section's
 * container) can be disabled directly or inherit it from the parent
 * component it is embedded in — the same cascade `CanBeDisabled` gives
 * individual components ({@see \Sylament\Schemas\Components\Concerns\CanBeDisabled}),
 * one level up.
 */
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
