<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Sylament\Schemas\Components\Component;

/**
 * Ports Filament's identically-named base concern's core: `disabled()` /
 * `isDisabled()` / `isEnabled()`, plus the `saved()` coupling that keeps a
 * disabled field out of `getState()` (via {@see HasState::isDehydrated()}
 * falling back to {@see HasState::isSaved()}). `disabledOn()` is **not**
 * ported — it gates on an `Operation` string/instanceof, a concept schemas
 * has no equivalent of yet; see docs/progress/schemas/can-be-disabled.md.
 *
 * Security: disabling a field prevents it from being saved via this coupling,
 * but a skilled user can manipulate the client to bypass the disabled state
 * (it is just an HTML attribute). Always enforce authorization server-side
 * (e.g. in a form's `mutateFormDataBeforeSave()` or a policy) for sensitive
 * fields — never rely on `disabled()` alone.
 */
trait CanBeDisabled
{
    protected bool|Closure $isDisabled = false;

    public function disabled(bool|Closure $condition = true): static
    {
        $this->isDisabled = $condition;
        $this->saved(fn (Component $component): bool => ! $component->evaluate($condition));

        return $this;
    }

    public function isDisabled(): bool
    {
        if ($this->evaluate($this->isDisabled)) {
            return true;
        }
        return (bool) $this->getContainer()->isDisabled();
    }

    public function isEnabled(): bool
    {
        return ! $this->isDisabled();
    }
}
