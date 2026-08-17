<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;

use function Sylament\Support\blank;

trait HasAction
{
    protected Closure|string|null $action = null;

    protected bool|Closure|null $isLiveComponentClickHandlerEnabled = null;

    public function action(Closure|string|null $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getAction(): Closure|string|null
    {
        return $this->action;
    }

    public function getClientClickHandler(): ?string
    {
        return is_string($this->action) ? $this->action : null;
    }

    /**
     * Whether this action wires a click of its own. Turning it off renders the
     * control with no click binding at all, for a caller that supplies the
     * behaviour from the markup around it.
     */
    public function liveComponentClickHandlerEnabled(bool|Closure|null $condition = true): static
    {
        $this->isLiveComponentClickHandlerEnabled = $condition;

        return $this;
    }

    public function isLiveComponentClickHandlerEnabled(): bool
    {
        $isEnabled = $this->evaluate($this->isLiveComponentClickHandlerEnabled);

        if ($isEnabled !== null) {
            return (bool) $isEnabled;
        }

        return blank($this->getUrl()) && ! $this->canSubmitForm();
    }
}
