<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

use function Sylament\Support\filled;

/**
 * Filament folds the Schema-side fallbacks into these getters behind
 * `$this instanceof Component` checks; here the trait carries the component
 * flavor (container delegation) and Schema overrides the getters, so each
 * context stays statically typed — mirroring `HasGap` / `HasColumns`.
 */
trait HasStateBindingModifiers
{
    protected bool|Closure|null $isLive = null;

    protected bool $isLiveOnBlur = false;

    protected int|string|null $liveDebounce = null;

    public function live(bool $onBlur = false, int|string|null $debounce = null, bool|Closure|null $condition = true): static
    {
        $this->isLive = $condition;
        $this->isLiveOnBlur = $onBlur;
        $this->liveDebounce = $debounce;

        return $this;
    }

    public function reactive(): static
    {
        $this->live();

        return $this;
    }

    public function lazy(): static
    {
        $this->live(onBlur: true);

        return $this;
    }

    public function debounce(int|string|null $delay = 500): static
    {
        $this->live(debounce: $delay);

        return $this;
    }

    /**
     * Emit this component's Symfony UX `data-model` binding against its absolute
     * state path. The modifier grammar (`modifier|…|path`, path last) is parsed by
     * ux-live-component's `live_controller.js` (`get_model_binding_default`):
     * `norender` suppresses the re-render (state still syncs for the next submit),
     * `on(change)` switches the trigger event to blur/change, `debounce(<ms>)`
     * overrides the debounce; a bare path binds live on `input` with the default
     * debounce.
     */
    public function getDataModel(): ?string
    {
        $statePath = $this->getStatePath();

        if (! $this->isLive()) {
            return "norender|{$statePath}";
        }

        if ($this->isLiveOnBlur()) {
            return "on(change)|{$statePath}";
        }

        $debounce = $this->getLiveDebounce();
        if (filled($debounce)) {
            return "debounce({$debounce})|{$statePath}";
        }

        return $statePath;
    }

    public function isLive(): bool
    {
        return $this->evaluate($this->isLive) ?? $this->getContainer()->isLive();
    }

    public function isLiveOnBlur(): bool
    {
        if ($this->evaluate($this->isLive) !== null) {
            return $this->isLiveOnBlur;
        }

        return $this->getContainer()->isLiveOnBlur();
    }

    public function isLiveDebounced(): bool
    {
        if ($this->isLiveOnBlur) {
            return false;
        }

        if (filled($this->liveDebounce)) {
            return true;
        }

        return $this->getContainer()->isLiveDebounced();
    }

    public function getLiveDebounce(): int|string|null
    {
        if ($this->isLiveOnBlur) {
            return null;
        }

        if (filled($this->liveDebounce)) {
            return $this->liveDebounce;
        }

        return $this->getContainer()->getLiveDebounce();
    }
}
