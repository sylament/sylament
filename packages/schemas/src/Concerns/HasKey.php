<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Closure;

trait HasKey
{
    protected string|Closure|null $key = null;

    protected ?string $cachedAbsoluteKey = null;

    protected bool $hasCachedAbsoluteKey = false;

    protected ?string $cachedInheritanceKey = null;

    protected bool $hasCachedInheritanceKey = false;

    public function key(string|Closure|null $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getKey(bool $isAbsolute = true): ?string
    {
        if ($isAbsolute && $this->hasCachedAbsoluteKey) {
            return $this->cachedAbsoluteKey;
        }

        $key = $this->evaluate($this->key);

        if ($key !== null) {
            // Security: strip characters that could break out of a quoted HTML attribute
            // or a JS string, so every downstream sink embedding this key is safe without
            // per-sink escaping. Legitimate keys never contain these characters.
            $key = preg_replace('/[<>"\'`\x00-\x1F\x7F]/', '', $key);
        }

        if (!$isAbsolute) {
            return $key;
        }

        $keyComponents = [];

        $parentComponentInheritanceKey = $this->getParentComponent()?->getInheritanceKey();
        if ($parentComponentInheritanceKey !== null && mb_trim($parentComponentInheritanceKey) !== '') {
            $keyComponents[] = $parentComponentInheritanceKey;
        }

        if ($key !== null && mb_trim($key) !== '') {
            $keyComponents[] = $key;
        }

        return $this->cacheAbsoluteKey(implode('.', $keyComponents));
    }

    public function getInheritanceKey(): ?string
    {
        if ($this->hasCachedInheritanceKey) {
            return $this->cachedInheritanceKey;
        }

        $key = $this->getKey();

        if ($key !== null && mb_trim($key) !== '') {
            return $this->cacheInheritanceKey($key);
        }

        return $this->cacheInheritanceKey($this->getParentComponent()?->getInheritanceKey());
    }

    protected function cacheAbsoluteKey(?string $key): ?string
    {
        try {
            return $this->cachedAbsoluteKey = $key;
        } finally {
            $this->hasCachedAbsoluteKey = true;
        }
    }

    protected function cacheInheritanceKey(?string $key): ?string
    {
        try {
            return $this->cachedInheritanceKey = $key;
        } finally {
            $this->hasCachedInheritanceKey = true;
        }
    }

    protected function flushCachedAbsoluteKey(): void
    {
        $this->cachedAbsoluteKey = null;
        $this->hasCachedAbsoluteKey = false;
    }

    protected function flushCachedInheritanceKey(): void
    {
        $this->cachedInheritanceKey = null;
        $this->hasCachedInheritanceKey = false;
    }
}
