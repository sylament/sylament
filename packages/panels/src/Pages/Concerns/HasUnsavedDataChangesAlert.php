<?php

declare(strict_types=1);

namespace Sylament\Panels\Pages\Concerns;

use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * `$this->data` is the composing page's `#[LiveProp(writable: true)] public
 * array $data` — the trait is composed only by pages that declare one.
 */
trait HasUnsavedDataChangesAlert
{
    /**
     * The marker of the last saved state. Its value is never reproduced in the
     * browser: the controller only notices it changed and re-takes its own
     * baseline.
     */
    #[LiveProp]
    public ?string $savedDataHash = null;

    protected ?bool $hasUnsavedDataChangesAlert = null;

    public function hasUnsavedDataChangesAlert(): bool
    {
        return $this->hasUnsavedDataChangesAlert ?? $this->getPanel()->hasUnsavedChangesAlerts();
    }

    protected function rememberData(): void
    {
        if (! $this->hasUnsavedDataChangesAlert()) {
            return;
        }

        $this->savedDataHash = md5(str_replace('\\', '', (string) json_encode($this->data, JSON_UNESCAPED_UNICODE)));
    }
}
