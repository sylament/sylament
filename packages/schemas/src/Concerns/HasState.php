<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Closure;

use function Sylament\Support\filled;

trait HasState
{
    protected ?string $statePath = null;

    protected ?string $cachedAbsoluteStatePath = null;

    protected bool $hasCachedAbsoluteStatePath = false;

    /**
     * @var array<string, bool>|null
     */
    protected ?array $dehydratedComponentsCache = null;

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
     * The whole raw bag this schema is bound to — the seed the dehydration walk
     * prunes down. A blank state path (top-level binding) has no single bag to
     * read, so it seeds empty.
     *
     * @return array<string, mixed>
     */
    public function getRawState(): array
    {
        $statePath = $this->getStatePath();

        if ($statePath === null || $statePath === '') {
            return [];
        }

        $state = $this->getPropertyAccessor()->get($this->getLiveComponent(), $statePath);

        if (! is_array($state)) {
            return [];
        }

        /** @var array<string, mixed> $state */
        return $state;
    }

    /**
     * @param array<string, mixed> $state
     */
    public function rawState(array $state): static
    {
        $statePath = $this->getStatePath();

        if (filled($statePath)) {
            $this->getPropertyAccessor()->set($this->getLiveComponent(), $statePath, $state);

            return $this;
        }

        foreach ($state as $key => $value) {
            $this->getPropertyAccessor()->set($this->getLiveComponent(), (string) $key, $value);
        }

        return $this;
    }

    /**
     * @param object|array<string, mixed>|null $state
     */
    public function fill(object|array|null $state = null): static
    {
        if (is_object($state)) {
            $state = $this->readStateFromRecord($state);
        }

        if ($state === null) {
            $this->hydrateState(applyDefaults: true);
        } else {
            $this->rawState($state);
            $this->hydrateState(applyDefaults: false);
        }

        $this->callAfterStateHydrated();
        $this->fillStateWithNull();

        return $this;
    }

    public function hydrateState(bool $applyDefaults = true): void
    {
        $appliedStateCastPaths = [];
        $hydratedDefaultState = [];

        foreach ($this->getFlatComponents(withActions: false) as $component) {
            $component->hydrateState($applyDefaults, $appliedStateCastPaths, $hydratedDefaultState);
        }
    }

    public function fillStateWithNull(): void
    {
        foreach ($this->getFlatComponents(withActions: false) as $component) {
            $component->fillStateWithNull();
        }
    }

    public function callAfterStateHydrated(): void
    {
        foreach ($this->getComponents(withActions: false) as $component) {
            foreach ($component->getChildSchemas() as $childSchema) {
                $childSchema->callAfterStateHydrated();
            }

            $component->callAfterStateHydrated();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getState(bool $shouldCallHooksBefore = true, ?Closure $afterValidate = null): array
    {
        $this->validate();

        $state = $this->assembleDehydratedState($shouldCallHooksBefore);

        if ($afterValidate instanceof Closure) {
            $afterValidate($state);
        }

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    public function getStateSnapshot(): array
    {
        return $this->assembleDehydratedState();
    }

    /**
     * @return array<string, mixed>
     */
    protected function assembleDehydratedState(bool $shouldCallHooksBefore = true): array
    {
        $this->dehydratedComponentsCache = null;

        $accessor = $this->getPropertyAccessor();
        $statePath = $this->getStatePath();

        if ($statePath !== null && $statePath !== '') {
            /** @var array<string, mixed> $state */
            $state = [];
            $accessor->setInArray($state, $statePath, $this->getRawState());
        } else {
            $state = $this->getRawState();
        }

        if ($shouldCallHooksBefore) {
            $this->callBeforeStateDehydrated($state);
        }

        $this->dehydrateState($state);
        $this->mutateDehydratedState($state);

        if ($statePath !== null && $statePath !== '') {
            $unwrapped = $accessor->getFromArray($state, $statePath);
            $state = is_array($unwrapped) ? $unwrapped : [];
        }

        /** @var array<string, mixed> $state */
        return $state;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function dehydrateState(array &$state = [], bool $isDehydrated = true): array
    {
        foreach ($this->getComponents(withActions: false) as $component) {
            $component->dehydrateState($state, $isDehydrated);
        }

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function mutateDehydratedState(array &$state = []): array
    {
        $accessor = $this->getPropertyAccessor();

        foreach ($this->getComponents(withActions: false) as $component) {
            if (! $component->isDehydrated()) {
                continue;
            }

            foreach ($component->getChildSchemas() as $childSchema) {
                $childSchema->mutateDehydratedState($state);
            }

            if (! filled($component->getStatePath(isAbsolute: false))) {
                continue;
            }

            if (! $component->mutatesDehydratedState()) {
                continue;
            }

            $componentStatePath = $component->getStatePath();

            if ($componentStatePath === null) {
                continue;
            }

            $accessor->setInArray(
                $state,
                $componentStatePath,
                $component->mutateDehydratedState($accessor->getFromArray($state, $componentStatePath)),
            );
        }

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     */
    public function callBeforeStateDehydrated(array &$state = []): void
    {
        foreach ($this->getComponents(withActions: false) as $component) {
            if ($component->isHiddenAndNotDehydratedWhenHidden()) {
                continue;
            }

            $component->callBeforeStateDehydrated($state);

            foreach ($component->getChildSchemas() as $childSchema) {
                $childSchema->callBeforeStateDehydrated($state);
            }
        }
    }

    public function hasDehydratedComponent(string $statePath): bool
    {
        if ($this->dehydratedComponentsCache === null) {
            $this->dehydratedComponentsCache = $this->buildDehydratedComponentsCache();
        }

        return $this->dehydratedComponentsCache[$statePath] ?? false;
    }

    /**
     * Map every dehydrated, path-bearing component in the tree to its absolute
     * state path, so a non-dehydrated component sharing a path can tell whether
     * to leave the key for a dehydrated owner. Built once per {@see getState()}.
     *
     * @return array<string, bool>
     */
    protected function buildDehydratedComponentsCache(): array
    {
        $cache = [];

        foreach ($this->getComponents(withActions: false) as $component) {
            if (! $component->isDehydrated()) {
                continue;
            }

            $componentStatePath = $component->getStatePath();

            if ($component->hasStatePath() && $componentStatePath !== null) {
                $cache[$componentStatePath] = true;
            }

            foreach ($component->getChildSchemas() as $childSchema) {
                $cache = [...$cache, ...$childSchema->buildDehydratedComponentsCache()];
            }
        }

        return $cache;
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
