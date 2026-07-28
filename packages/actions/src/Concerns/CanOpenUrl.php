<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;

/**
 * Ports Filament's actions `CanOpenUrl`, minimal per docs/progress/actions/action.md.
 * `getUrl()` keeps Filament's `hasModal()` precedence guard (an explicit
 * `->modal()` suppresses the URL; implied modal content does not) but drops the
 * Livewire `getDefaultActionUrl()` fallback (the `getDefaultAction*` host-hook
 * cluster is deferred); `postToUrl()`/`shouldPostToUrl()` are deferred (no POST
 * actions in the MVP). A component with a URL renders as an `<a>` link and
 * skips the mount wiring. The composing class must supply `hasModal()`
 * (`CanOpenModal` does).
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
        if ($this->hasModal()) {
            return null;
        }

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
