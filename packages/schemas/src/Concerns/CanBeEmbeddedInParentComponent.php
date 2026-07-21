<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Closure;

trait CanBeEmbeddedInParentComponent
{
    protected bool|Closure $isEmbeddedInParentComponent = false;

    public function embeddedInParentComponent(bool|Closure $condition = true): static
    {
        $this->isEmbeddedInParentComponent = $condition;

        return $this;
    }

    public function isEmbeddedInParentComponent(): bool
    {
        return $this->evaluate($this->isEmbeddedInParentComponent);
    }
}
