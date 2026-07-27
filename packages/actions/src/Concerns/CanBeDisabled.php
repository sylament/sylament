<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;

/**
 * Ports Filament's actions `CanBeDisabled`, simplified per
 * docs/progress/actions/action.md: the authorization branches (`hasAuthorizationNotification()`,
 * `hasAuthorizationTooltip()`, `isAuthorized()`) are dropped, so a component is
 * disabled when its `$isDisabled` condition is truthy or it is hidden. Requires
 * {@see CanBeHidden} on the same class.
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

        return $this->isHidden();
    }

    public function isEnabled(): bool
    {
        return ! $this->isDisabled();
    }

    public function hasDisabled(): bool
    {
        return $this->isDisabled !== false;
    }
}
