<?php

declare(strict_types=1);

namespace Sylament\Panels\Panel\Concerns;

use Closure;
use InvalidArgumentException;
use Sylament\Panels\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Sylament\Panels\GlobalSearch\Providers\DefaultGlobalSearchProvider;

trait HasGlobalSearch
{
    protected string|Closure|null $globalSearchDebounce = null;

    /**
     * @var array<string>
     */
    protected array $globalSearchKeyBindings = [];

    /**
     * @var class-string<GlobalSearchProvider>|bool
     */
    protected string|bool $globalSearchProvider = true;

    protected string|Closure|null $globalSearchFieldSuffix = null;

    /**
     * @param class-string|bool $provider
     */
    public function globalSearch(string|bool $provider = true): static
    {
        if (is_string($provider) && ! is_subclass_of($provider, GlobalSearchProvider::class)) {
            throw new InvalidArgumentException(sprintf('Global search provider %s does not implement the [%s] interface.', $provider, GlobalSearchProvider::class));
        }

        $this->globalSearchProvider = $provider;

        return $this;
    }

    public function globalSearchDebounce(string|Closure|null $debounce): static
    {
        $this->globalSearchDebounce = $debounce;

        return $this;
    }

    /**
     * @param array<string> $keyBindings
     */
    public function globalSearchKeyBindings(array $keyBindings): static
    {
        $this->globalSearchKeyBindings = $keyBindings;

        return $this;
    }

    public function globalSearchFieldSuffix(string|Closure|null $suffix): static
    {
        $this->globalSearchFieldSuffix = $suffix;

        return $this;
    }

    public function getGlobalSearchDebounce(): string
    {
        return $this->evaluate($this->globalSearchDebounce) ?? '500ms';
    }

    /**
     * The debounce as ux-live-component's `debounce(N)` modifier wants it —
     * Filament's duration strings (`500ms`, `1s`, bare numbers) translated,
     * anything unparseable falling back to 500.
     */
    public function getGlobalSearchDebounceMilliseconds(): int
    {
        if (preg_match('/^(\d+(?:\.\d+)?)\s*(ms|s)?$/', mb_trim($this->getGlobalSearchDebounce()), $matches) !== 1) {
            return 500;
        }

        $value = (float) $matches[1];

        return (int) round(($matches[2] ?? 'ms') === 's' ? $value * 1000 : $value);
    }

    /**
     * @return array<string>
     */
    public function getGlobalSearchKeyBindings(): array
    {
        return $this->globalSearchKeyBindings;
    }

    public function getGlobalSearchFieldSuffix(): ?string
    {
        return $this->evaluate($this->globalSearchFieldSuffix);
    }

    /**
     * Filament answers an `app()`-resolved provider instance; a panel here is a
     * value object, so it answers the class and the caller resolves it through
     * `GlobalSearchProviderRegistry`. `null` is search switched off.
     *
     * @return class-string<GlobalSearchProvider>|null
     */
    public function getGlobalSearchProviderClass(): ?string
    {
        $provider = $this->globalSearchProvider;

        if ($provider === false) {
            return null;
        }

        if ($provider === true) {
            return DefaultGlobalSearchProvider::class;
        }

        return $provider;
    }

    public function isGlobalSearchEnabled(): bool
    {
        if ($this->getGlobalSearchProviderClass() === null) {
            return false;
        }

        foreach ($this->getResources() as $resourceClass) {
            if ($this->getResourceRegistry()->get($resourceClass)->canGloballySearch()) {
                return true;
            }
        }

        return false;
    }
}
