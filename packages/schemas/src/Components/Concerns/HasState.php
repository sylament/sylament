<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Sylament\Schemas\Components\Component;
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

    protected bool|Closure $isDehydrated = true;

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

    public function state(mixed $state): static
    {
        $this->rawState($state);

        return $this;
    }

    public function getState(): mixed
    {
        return $this->getRawState();
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
        return $this->getOldRawState();
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

    public function hydrateState(): void
    {
        if (! $this->hasStatePath()) {
            return;
        }

        if ($this->hasDefaultState) {
            $this->state($this->getDefaultState());
        }
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

    public function isDehydrated(): bool
    {
        return (bool) $this->evaluate($this->isDehydrated);
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
