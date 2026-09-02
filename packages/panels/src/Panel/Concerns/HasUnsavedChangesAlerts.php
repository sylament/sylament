<?php

declare(strict_types=1);

namespace Sylament\Panels\Panel\Concerns;

use Closure;

trait HasUnsavedChangesAlerts
{
    protected bool|Closure $hasUnsavedChangesAlerts = false;

    public function unsavedChangesAlerts(bool|Closure $condition = true): static
    {
        $this->hasUnsavedChangesAlerts = $condition;

        return $this;
    }

    public function hasUnsavedChangesAlerts(): bool
    {
        return (bool) $this->evaluate($this->hasUnsavedChangesAlerts);
    }
}
