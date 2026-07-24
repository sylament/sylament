<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;

/**
 * Ports Filament's actions `CanOpenUrl`, minimal per docs/parity/actions.md §1.
 * `getUrl()` drops Filament's `hasModal()` guard and the Livewire
 * `getDefaultActionUrl()` fallback (neither exists here); `postToUrl()`/
 * `shouldPostToUrl()` are deferred (no POST actions in the MVP). A component
 * with a URL renders as an `<a>` link and skips the mount wiring.
 */
trait CanOpenUrl
{
    protected bool|Closure $shouldOpenUrlInNewTab = false;

    protected string|Closure|null $url = null;

    public function openUrlInNewTab(bool|Closure $condition = true): static
    {
        $this->shouldOpenUrlInNewTab = $condition;

        return $this;
    }

    public function url(string|Closure|null $url, bool|Closure|null $shouldOpenInNewTab = null): static
    {
        if ($shouldOpenInNewTab !== null) {
            $this->openUrlInNewTab($shouldOpenInNewTab);
        }

        $this->url = $url;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->evaluate($this->url);
    }

    public function shouldOpenUrlInNewTab(): bool
    {
        return (bool) $this->evaluate($this->shouldOpenUrlInNewTab);
    }

    public function hasUrl(): bool
    {
        return $this->url !== null;
    }
}
