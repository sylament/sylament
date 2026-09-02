<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

use function Sylament\Support\filled;

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
     * The `data-model` directive this component's modifiers build, over its own
     * state path or over a path the caller names — a component rendering several
     * bound inputs binds each of them at the same liveness.
     */
    public function getDataModel(?string $statePath = null): ?string
    {
        $statePath ??= $this->getStatePath();

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
