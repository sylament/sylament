<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\StateCasts\Contracts\StateCast;
use Sylament\Schemas\Components\Utilities\Get;
use Sylament\Schemas\Components\Utilities\Set;
use Sylament\Schemas\State\PropertyAccessor;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

trait HasState
{
    protected ?string $statePath = null;

    protected ?string $cachedAbsoluteStatePath = null;

    protected bool $hasCachedAbsoluteStatePath = false;

    protected mixed $defaultState = null;

    protected bool $hasDefaultState = false;

    protected ?Closure $afterStateHydrated = null;

    /**
     * @var array<Closure>
     */
    protected array $afterStateUpdated = [];

    protected bool|Closure|null $isDehydrated = null;

    protected bool|Closure $isSaved = true;

    protected bool|Closure $isDehydratedWhenHidden = false;

    protected ?Closure $beforeStateDehydrated = null;

    protected bool $shouldUpdateValidatedStateAfterBeforeStateDehydratedRuns = false;

    protected ?Closure $dehydrateStateUsing = null;

    protected ?Closure $mutateDehydratedStateUsing = null;

    /**
     * @var array<StateCast|Closure>
     */
    protected array $stateCasts = [];

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

        if (filled($containerStatePath = $this->getContainer()->getStatePath())) {
            $pathComponents[] = $containerStatePath;
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

    public function getRawState(): mixed
    {
        $statePath = $this->getStatePath();

        if (blank($statePath)) {
            return null;
        }

        return PropertyAccessor::getInstance()->get($this->getLiveComponent(), $statePath);
    }

    public function rawState(mixed $state): static
    {
        $statePath = $this->getStatePath();

        if (blank($statePath)) {
            return $this;
        }

        PropertyAccessor::getInstance()->set($this->getLiveComponent(), $statePath, $this->evaluate($state));

        return $this;
    }

    public function stateCast(StateCast|Closure $cast): static
    {
        $this->stateCasts[] = $cast;

        return $this;
    }

    /**
     * @return array<StateCast>
     */
    public function getStateCasts(): array
    {
        $casts = $this->getDefaultStateCasts();

        foreach ($this->stateCasts as $cast) {
            $casts[] = $this->evaluate($cast);
        }

        return $casts;
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [];
    }

    public function hasCustomStateCasts(): bool
    {
        return $this->stateCasts !== [];
    }

    public function state(mixed $state): static
    {
        foreach (array_reverse($this->getStateCasts()) as $stateCast) {
            $state = $stateCast->set($state);
        }

        $this->rawState($state);

        return $this;
    }

    public function getState(): mixed
    {
        $state = $this->getRawState();

        foreach ($this->getStateCasts() as $stateCast) {
            $state = $stateCast->get($state);
        }

        return $state;
    }

    public function default(mixed $state): static
    {
        $this->defaultState = $state;
        $this->hasDefaultState = true;

        return $this;
    }

    public function getDefaultState(): mixed
    {
        return $this->evaluate($this->defaultState);
    }

    public function afterStateHydrated(?Closure $callback): static
    {
        $this->afterStateHydrated = $callback;

        return $this;
    }

    public function callAfterStateHydrated(): static
    {
        if ($this->afterStateHydrated !== null) {
            $this->evaluate($this->afterStateHydrated);
        }

        return $this;
    }

    public function formatStateUsing(?Closure $callback): static
    {
        $this->afterStateHydrated(fn (Component $component): Component => $component->state($component->evaluate($callback)));

        return $this;
    }

    public function afterStateUpdated(?Closure $callback): static
    {
        if (!$callback instanceof Closure) {
            $this->afterStateUpdated = [];

            return $this;
        }

        $this->afterStateUpdated[] = $callback;

        return $this;
    }

    public function clearAfterStateUpdatedHooks(): static
    {
        $this->afterStateUpdated = [];

        return $this;
    }

    /**
     * Fire every registered `afterStateUpdated` hook. Invoked by the host's
     * `#[PostHydrate]` pass for each component whose state changed on the
     * round-trip; each hook receives the pre-change `$old` (cast) / `$oldRaw`
     * (raw) values injected by name.
     */
    public function callAfterStateUpdatedHooks(): static
    {
        foreach ($this->afterStateUpdated as $callback) {
            $this->callAfterStateUpdatedHook($callback);
        }

        return $this;
    }

    public function getOldState(): mixed
    {
        $state = $this->getOldRawState();

        if (blank($state)) {
            return null;
        }

        foreach ($this->getStateCasts() as $stateCast) {
            $state = $stateCast->get($state);
        }

        return $state;
    }

    public function getOldRawState(): mixed
    {
        return $this->getLiveComponent()->getOldSchemaState($this->getStatePath());
    }

    protected function callAfterStateUpdatedHook(Closure $hook): void
    {
        $this->evaluate($hook, [
            'old' => $this->getOldState(),
            'oldRaw' => $this->getOldRawState(),
        ]);
    }

    /**
     * @param array<string, true> $appliedStateCastPaths
     */
    public function hydrateState(bool $applyDefaults = true, array &$appliedStateCastPaths = []): void
    {
        if (! $this->hasStatePath()) {
            return;
        }

        if ($applyDefaults && $this->hasDefaultState) {
            $this->rawState($this->getDefaultState());
        }

        $stateCasts = $this->getStateCasts();

        if ($stateCasts === []) {
            return;
        }

        $statePath = $this->getStatePath();

        if (blank($statePath)) {
            return;
        }

        if (isset($appliedStateCastPaths[$statePath])) {
            return;
        }

        $rawState = $this->getRawState();
        $originalRawState = $rawState;

        foreach ($stateCasts as $stateCast) {
            $rawState = $stateCast->set($rawState);
        }

        if ($rawState !== $originalRawState) {
            $this->rawState($rawState);
        }

        $appliedStateCastPaths[$statePath] = true;
    }

    public function fillStateWithNull(): void
    {
        if (! $this->hasStatePath()) {
            return;
        }

        if ($this->getRawState() === null) {
            $this->state(null);
        }
    }

    public function dehydrated(bool|Closure $condition = true): static
    {
        $this->isDehydrated = $condition;

        return $this;
    }

    public function dehydratedWhenHidden(bool|Closure $condition = true): static
    {
        $this->isDehydratedWhenHidden = $condition;

        return $this;
    }

    public function saved(bool|Closure $condition = true): static
    {
        $this->isSaved = $condition;

        return $this;
    }

    public function dehydrateStateUsing(?Closure $callback): static
    {
        $this->dehydrateStateUsing = $callback;

        return $this;
    }

    public function beforeStateDehydrated(?Closure $callback, bool $shouldUpdateValidatedStateAfter = false): static
    {
        $this->beforeStateDehydrated = $callback;
        $this->shouldUpdateValidatedStateAfterBeforeStateDehydratedRuns = $shouldUpdateValidatedStateAfter;

        return $this;
    }

    public function mutateDehydratedStateUsing(?Closure $callback): static
    {
        $this->mutateDehydratedStateUsing = $callback;

        return $this;
    }

    public function isDehydrated(): bool
    {
        $isDehydrated = $this->evaluate($this->isDehydrated) ?? $this->isSaved();

        if (! $isDehydrated) {
            return false;
        }

        return ! $this->isHiddenAndNotDehydratedWhenHidden();
    }

    public function isDehydratedWhenHidden(): bool
    {
        return (bool) $this->evaluate($this->isDehydratedWhenHidden);
    }

    public function isSaved(): bool
    {
        return (bool) $this->evaluate($this->isSaved);
    }

    public function isHiddenAndNotDehydratedWhenHidden(): bool
    {
        if (! $this->isHidden()) {
            return false;
        }

        return ! $this->isDehydratedWhenHidden();
    }

    public function mutatesDehydratedState(): bool
    {
        return $this->mutateDehydratedStateUsing instanceof Closure;
    }

    public function mutateDehydratedState(mixed $state): mixed
    {
        if (! $this->mutateDehydratedStateUsing instanceof Closure) {
            return $state;
        }

        return $this->evaluate($this->mutateDehydratedStateUsing, [
            'state' => $state,
        ]);
    }

    /**
     * @param array<string, mixed> $state
     */
    public function callBeforeStateDehydrated(array &$state = []): static
    {
        if (! $this->beforeStateDehydrated instanceof Closure) {
            return $this;
        }

        $this->evaluate($this->beforeStateDehydrated);

        if ($this->shouldUpdateValidatedStateAfterBeforeStateDehydratedRuns) {
            $statePath = $this->getStatePath();

            if ($statePath !== null) {
                PropertyAccessor::getInstance()->setInArray($state, $statePath, $this->getState());
            }
        }

        return $this;
    }

    /**
     * The dehydration read: apply state casts forward (`get`) and the optional
     * `dehydrateStateUsing` transform, returning the single `[absolutePath => value]`
     * entry to merge into the assembled bag.
     *
     * @return array<string, mixed>
     */
    public function getStateToDehydrate(mixed $state): array
    {
        if ($state === '') {
            $state = null;
        }

        foreach ($this->getStateCasts() as $stateCast) {
            $state = $stateCast->get($state);
        }

        if ($this->dehydrateStateUsing instanceof Closure) {
            return [(string) $this->getStatePath() => $this->evaluate($this->dehydrateStateUsing, [
                'state' => $state,
            ])];
        }

        return [(string) $this->getStatePath() => $state];
    }

    /**
     * Merge this component's dehydrated value into the assembled `$state` bag,
     * pruning it (and only it) when not dehydrated. `$isDehydrated` is the
     * parent's verdict flowing down; a component ANDs it with its own
     * {@see isDehydrated()}.
     *
     * @param array<string, mixed> $state
     */
    public function dehydrateState(array &$state, bool $isDehydrated = true): void
    {
        if (!$isDehydrated || !$this->isDehydrated()) {
            if ($this->hasStatePath()) {
                $statePath = $this->getStatePath();

                if ($statePath === null) {
                    return;
                }

                if ($this->getRootContainer()->hasDehydratedComponent($statePath)) {
                    return;
                }

                if ($this->hasComponentWithStatePath($statePath)) {
                    PropertyAccessor::getInstance()->forgetFromArray($state, $this->getDescendantStatePathsToForget($statePath));

                    if (blank(PropertyAccessor::getInstance()->getFromArray($state, $statePath))) {
                        PropertyAccessor::getInstance()->forgetFromArray($state, $statePath);
                    }

                    return;
                }

                PropertyAccessor::getInstance()->forgetFromArray($state, $statePath);

                return;
            }

            foreach ($this->getChildSchemas() as $childSchema) {
                $childSchema->dehydrateState($state, isDehydrated: false);
            }

            return;
        }

        foreach ($this->getChildSchemas() as $childSchema) {
            $childSchema->dehydrateState($state, $isDehydrated);
        }

        if ($this->hasStatePath()) {
            $statePath = $this->getStatePath();

            if ($statePath === null) {
                return;
            }

            foreach ($this->getStateToDehydrate(PropertyAccessor::getInstance()->getFromArray($state, $statePath)) as $key => $value) {
                PropertyAccessor::getInstance()->setInArray($state, $key, $value);
            }
        }
    }

    /**
     * Whether another component in the same schema scope shares the given
     * absolute `statePath`. The scope walks up through pathless layout ancestors
     * (Section, Tabs, …) until a `statePath`-bearing ancestor bounds it.
     */
    protected function hasComponentWithStatePath(string $statePath): bool
    {
        $container = $this->getContainer();

        while (($parentComponent = $container->getParentComponent()) !== null) {
            $parentContainer = $parentComponent->getContainer();

            if ($parentComponent->hasStatePath()) {
                break;
            }

            if ($parentContainer->getStatePath() !== $container->getStatePath()) {
                break;
            }

            $container = $parentContainer;
        }

        foreach ($container->getFlatComponents(withActions: false) as $component) {
            if ($component === $this) {
                continue;
            }

            if ($component->hasStatePath() && $component->getStatePath() === $statePath) {
                return true;
            }
        }

        return false;
    }

    /**
     * The absolute paths owned by this component's descendants that live under
     * `$statePath`, so a shared-path prune removes only its own subtree.
     *
     * @return array<string>
     */
    protected function getDescendantStatePathsToForget(string $statePath): array
    {
        $descendantStatePathPrefix = "{$statePath}.";
        $paths = [];

        foreach ($this->getChildSchemas() as $childSchema) {
            foreach ($childSchema->getFlatComponents(withActions: false) as $component) {
                if (! $component->hasStatePath()) {
                    continue;
                }

                $childStatePath = $component->getStatePath();

                if ($childStatePath !== null && str_starts_with($childStatePath, $descendantStatePathPrefix)) {
                    $paths[] = $childStatePath;
                }
            }
        }

        return $paths;
    }

    public function resolveRelativeStatePath(string|Component $path = '', bool $isAbsolute = false): string
    {
        if ($path instanceof Component) {
            return $path->getStatePath() ?? '';
        }

        if (str_starts_with($path, '/')) {
            $isAbsolute = true;
            $path = mb_substr($path, 1);
        }

        if ($isAbsolute) {
            return $path;
        }

        $containerPath = $this->getContainer()->getStatePath();

        while (str_starts_with($path, '../')) {
            $containerPath = ($containerPath !== null && str_contains($containerPath, '.'))
                ? mb_substr($containerPath, 0, (int) mb_strrpos($containerPath, '.'))
                : null;

            $path = mb_substr($path, 3);
        }

        if (blank($containerPath)) {
            return $path;
        }

        return mb_ltrim($path, './') !== '' ? "{$containerPath}.{$path}" : $containerPath;
    }

    public function makeGetUtility(): Get
    {
        return new Get($this);
    }

    public function makeSetUtility(): Set
    {
        return new Set($this);
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
