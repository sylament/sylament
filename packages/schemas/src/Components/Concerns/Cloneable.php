<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

trait Cloneable
{
    /**
     * @var array<Closure>
     */
    protected array $afterCloned = [];

    public function afterCloned(Closure $callback): static
    {
        $this->afterCloned[] = $callback;

        return $this;
    }

    public function getClone(): static
    {
        $clone = clone $this;
        $clone->flushCachedAbsoluteKey();
        $clone->flushCachedAbsoluteStatePath();
        $clone->flushCachedAbsoluteInheritanceKey();
        $clone->cloneChildComponents();

        foreach ($this->afterCloned as $callback) {
            $clone->evaluate($callback->bindTo($clone), [
                'clone' => $clone,
                'original' => $this,
            ]);
        }

        return $clone;
    }
}
