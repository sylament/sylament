<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;

/**
 * Ports Filament's actions `HasAction`. A string value is a click handler:
 * Filament emits it verbatim as a client-side Livewire expression, while
 * Sylament emits it as a live-action method name through `live_action()` —
 * `getClientClickHandler()` is the Sylament port of
 * `getLivewireClickHandler()`'s string arm (there is no arbitrary-JS surface).
 */
trait HasAction
{
    protected Closure|string|null $action = null;

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
}
