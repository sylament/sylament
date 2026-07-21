<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

trait HasKey
{
    protected string|Closure|null $key = null;

    protected bool $isKeyInheritable = true;

    protected ?string $cachedAbsoluteKey = null;

    protected bool $hasCachedAbsoluteKey = false;

    protected ?string $cachedAbsoluteInheritanceKey = null;

    protected bool $hasCachedAbsoluteInheritanceKey = false;

    public function key(string|Closure|null $key, bool $isInheritable = true): static
    {
        $this->key = $key;
        $this->isKeyInheritable = $isInheritable;

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

        if (blank($key)) {
            return $this->cacheAbsoluteKey(null);
        }

        $keyComponents = [];

        $containerInheritanceKey = $this->getContainer()->getInheritanceKey();
        if (filled($containerInheritanceKey)) {
            $keyComponents[] = $containerInheritanceKey;
        }

        $keyComponents[] = $key;

        return $this->cacheAbsoluteKey(implode('.', $keyComponents));
    }

    public function getInheritanceKey(bool $isAbsolute = true): ?string
    {
        if ($isAbsolute && $this->hasCachedAbsoluteInheritanceKey) {
            return $this->cachedAbsoluteInheritanceKey;
        }

        if (!$isAbsolute) {
            return $this->isKeyInheritable() ? $this->getKey(isAbsolute: false) : null;
        }

        if ($this->isKeyInheritable()) {
            $key = $this->getKey();

            if (filled($key)) {
                return $this->cacheAbsoluteInheritanceKey($key);
            }
        }

        return $this->cacheAbsoluteInheritanceKey($this->getContainer()->getInheritanceKey());
    }

    public function isKeyInheritable(): bool
    {
        return $this->isKeyInheritable;
    }

    protected function cacheAbsoluteKey(?string $key): ?string
    {
        try {
            return $this->cachedAbsoluteKey = $key;
        } finally {
            $this->hasCachedAbsoluteKey = true;
        }
    }

    protected function cacheAbsoluteInheritanceKey(?string $key): ?string
    {
        try {
            return $this->cachedAbsoluteInheritanceKey = $key;
        } finally {
            $this->hasCachedAbsoluteInheritanceKey = true;
        }
    }

    protected function flushCachedAbsoluteKey(): void
    {
        $this->cachedAbsoluteKey = null;
        $this->hasCachedAbsoluteKey = false;
    }

    protected function flushCachedAbsoluteInheritanceKey(): void
    {
        $this->cachedAbsoluteInheritanceKey = null;
        $this->hasCachedAbsoluteInheritanceKey = false;
    }
}
