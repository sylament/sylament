<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Sylament\Schemas\State\PropertyAccessor;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

trait HasState
{
    protected ?string $statePath = null;

    protected ?string $cachedAbsoluteStatePath = null;

    protected bool $hasCachedAbsoluteStatePath = false;

    public function statePath(?string $path): static
    {
        $this->statePath = $path;
        $this->flushCachedAbsoluteStatePath();

        return $this;
    }

    public function getStatePath(bool $isAbsolute = true): ?string
    {
        if (! $isAbsolute) {
            // Security: strip characters that could break out of a quoted HTML attribute
            // or a JS string, so every downstream sink embedding this path raw is safe
            // without per-sink escaping. Legitimate state paths never contain them.
            return $this->statePath === null
                ? null
                : preg_replace('/[<>"\'`\x00-\x1F\x7F]/', '', $this->statePath);
        }

        if ($this->hasCachedAbsoluteStatePath) {
            return $this->cachedAbsoluteStatePath;
        }

        $pathComponents = [];

        if (filled($parentComponentStatePath = $this->getParentComponent()?->getStatePath())) {
            $pathComponents[] = $parentComponentStatePath;
        }

        $ownStatePath = $this->getStatePath(isAbsolute: false);
        if (filled($ownStatePath)) {
            $pathComponents[] = $ownStatePath;
        }

        return $this->cacheAbsoluteStatePath(implode('.', $pathComponents));
    }

    public function hasStatePath(): bool
    {
        return filled($this->statePath);
    }

    /**
     * @param array<string, mixed> $state
     */
    public function rawState(array $state): static
    {
        $statePath = $this->getStatePath();

        if (filled($statePath)) {
            PropertyAccessor::getInstance()->set($this->getLiveComponent(), $statePath, $state);

            return $this;
        }

        foreach ($state as $key => $value) {
            PropertyAccessor::getInstance()->set($this->getLiveComponent(), (string) $key, $value);
        }

        return $this;
    }

    /**
     * @param array<string, mixed>|null $state
     */
    public function fill(?array $state = null): static
    {
        if ($state === null) {
            $this->hydrateState(applyDefaults: true);
        } else {
            $this->rawState($state);
            $this->hydrateState(applyDefaults: false);
        }

        $this->fillStateWithNull();
        $this->callAfterStateHydrated();

        return $this;
    }

    public function hydrateState(bool $applyDefaults = true): void
    {
        $appliedStateCastPaths = [];

        foreach ($this->getFlatComponents() as $component) {
            $component->hydrateState($applyDefaults, $appliedStateCastPaths);
        }
    }

    public function fillStateWithNull(): void
    {
        foreach ($this->getFlatComponents() as $component) {
            $component->fillStateWithNull();
        }
    }

    public function callAfterStateHydrated(): void
    {
        foreach ($this->getFlatComponents() as $component) {
            $component->callAfterStateHydrated();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getState(): array
    {
        $state = [];

        foreach ($this->getFlatComponents() as $component) {
            $ownStatePath = $component->getStatePath(isAbsolute: false);
            if (blank($ownStatePath)) {
                continue;
            }

            if (! $component->isDehydrated()) {
                continue;
            }

            $state[$ownStatePath] = $component->getState();
        }

        return $state;
    }

    protected function cacheAbsoluteStatePath(?string $path): ?string
    {
        try {
            return $this->cachedAbsoluteStatePath = $path;
        } finally {
            $this->hasCachedAbsoluteStatePath = true;
        }
    }

    protected function flushCachedAbsoluteStatePath(): void
    {
        $this->cachedAbsoluteStatePath = null;
        $this->hasCachedAbsoluteStatePath = false;
    }
}
