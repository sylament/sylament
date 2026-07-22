<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Closure;

/**
 * Filament folds the Schema-side fallbacks into these getters behind
 * `$this instanceof Schema` checks; here the trait carries the component
 * flavor (container delegation) and Schema overrides the getters, so each
 * context stays statically typed.
 */
trait HasGap
{
    protected bool|Closure|null $hasGap = null;

    protected bool|Closure|null $isDense = null;

    public function gap(bool|Closure|null $condition = true): static
    {
        $this->hasGap = $condition;

        return $this;
    }

    public function hasGap(): bool
    {
        return $this->evaluate($this->hasGap) ?? $this->getContainer()->hasGap();
    }

    public function dense(bool|Closure|null $condition = true): static
    {
        $this->isDense = $condition;

        return $this;
    }

    public function isDense(): bool
    {
        return $this->evaluate($this->isDense) ?? $this->getContainer()->isDense();
    }
}
