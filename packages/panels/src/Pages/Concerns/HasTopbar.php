<?php

declare(strict_types=1);

namespace Sylament\Panels\Pages\Concerns;

trait HasTopbar
{
    protected bool $hasTopbar = true;

    public function hasTopbar(): bool
    {
        return $this->hasTopbar;
    }
}
