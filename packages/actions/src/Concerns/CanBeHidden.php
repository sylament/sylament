<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;

/**
 * The visibility core of Filament's actions `CanBeHidden`, mirroring schemas'
 * simplified port ({@see \Sylament\Schemas\Components\Concerns\CanBeHidden}).
 * Filament's table/group/record cache and the authorization branch are
 * dropped for the confirm-only MVP. The `ActionGroup` child-iteration branch
 * is ported, but as an override of {@see \Sylament\Actions\ActionGroup::isHidden()}
 * rather than in this shared trait, since only `ActionGroup` needs it.
 */
trait CanBeHidden
{
    protected bool|Closure $isHidden = false;

    protected bool|Closure $isVisible = true;

    public function hidden(bool|Closure $condition = true): static
    {
        $this->isHidden = $condition;

        return $this;
    }

    public function visible(bool|Closure $condition = true): static
    {
        $this->isVisible = $condition;

        return $this;
    }

    public function isHidden(): bool
    {
        if ($this->evaluate($this->isHidden)) {
            return true;
        }

        return ! $this->evaluate($this->isVisible);
    }

    public function isVisible(): bool
    {
        return ! $this->isHidden();
    }
}
