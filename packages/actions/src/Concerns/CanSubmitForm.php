<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use function Sylament\Support\filled;

/**
 * Ports Filament's actions `CanSubmitForm`. `hasCanSubmitForm()` and
 * `hasFormId()` are not ported: both are trivial duplicates of
 * `canSubmitForm()` / `($formId !== null)` with no distinct caller anywhere
 * in Filament's own codebase.
 *
 * `getFormToSubmit()` and `getFormId()` currently have no consumer in
 * Sylament: in Filament they feed the rendered trigger's `wire:target`
 * (loading state scoped to the submitted form) and a `form=` attribute for a
 * button rendered outside its `<form>` element. Neither rendering path
 * exists here yet. Both are ported anyway for API parity, so a migrating
 * user's call to `submit()` / `formId()` does not fatal.
 */
trait CanSubmitForm
{
    protected bool $canSubmitForm = false;

    protected ?string $formToSubmit = null;

    protected ?string $formId = null;

    public function submit(?string $form): static
    {
        $this->canSubmitForm = filled($form);
        $this->formToSubmit = $form;

        return $this;
    }

    public function canSubmitForm(): bool
    {
        return $this->canSubmitForm;
    }

    public function getFormToSubmit(): ?string
    {
        return $this->formToSubmit;
    }

    public function formId(?string $id): static
    {
        $this->formId = $id;

        return $this;
    }

    public function getFormId(): ?string
    {
        return $this->formId;
    }
}
