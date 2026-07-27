<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Sylament\Schemas\Schema;
use Closure;
use Sylament\Schemas\Components\Component;

/**
 * The visibility core of Filament's `CanBeHidden`. `hidden()` / `visible()` and
 * the boolean resolvers gate both dehydration ({@see HasState::isDehydrated()}
 * drops a hidden component unless {@see HasState::dehydratedWhenHidden()} is set)
 * and render-loop skipping — a container's {@see HasComponents::getComponents()}
 * excludes invisible children when called with `withHidden: false`, as the schema
 * template does. `hiddenWhenAllChildComponentsHidden()` ships here too. Still
 * unported: `whenTruthy()` / `whenFalsy()` and the JS visibility variants (blocked
 * on live-component-state), `hiddenOn()` / `visibleOn()` (need an operation
 * concept), and the static visibility cache — see
 * docs/progress/schemas/can-be-hidden.md.
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

    public function hiddenWhenAllChildComponentsHidden(): static
    {
        $this->hidden(static fn (Component $component): bool => array_all($component->getChildSchemas(), fn (Schema $childSchema): bool => $childSchema->getComponents(withHidden: false) === []));

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
