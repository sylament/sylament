<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

/**
 * The visibility core of Filament's `CanBeHidden`, ported as the first consumer
 * (dehydration: {@see HasState::isDehydrated()} drops a hidden component unless
 * {@see HasState::dehydratedWhenHidden()} is set). Only `hidden()` / `visible()`
 * and the boolean resolvers land here; the render-loop skipping,
 * `whenTruthy()` / `whenFalsy()` (need `$get` resolution), `hiddenOn()` /
 * `visibleOn()` (need an operation concept), the JS visibility variants, and the
 * static visibility cache remain unported — see
 * docs/parity/schemas/component.md.
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
