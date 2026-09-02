<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;
use Sylament\Support\Enums\VerticalAlignment;

trait HasVerticalAlignment
{
    protected VerticalAlignment|string|Closure|null $verticalAlignment = null;

    public function verticalAlignment(VerticalAlignment|string|Closure|null $alignment): static
    {
        $this->verticalAlignment = $alignment;

        return $this;
    }

    public function verticallyAlignStart(bool|Closure $condition = true): static
    {
        return $this->verticalAlignment(fn (): ?VerticalAlignment => $this->evaluate($condition) ? VerticalAlignment::Start : null);
    }

    public function verticallyAlignCenter(bool|Closure $condition = true): static
    {
        return $this->verticalAlignment(fn (): ?VerticalAlignment => $this->evaluate($condition) ? VerticalAlignment::Center : null);
    }

    public function verticallyAlignEnd(bool|Closure $condition = true): static
    {
        return $this->verticalAlignment(fn (): ?VerticalAlignment => $this->evaluate($condition) ? VerticalAlignment::End : null);
    }

    public function getVerticalAlignment(): VerticalAlignment|string|null
    {
        $alignment = $this->evaluate($this->verticalAlignment);

        if (!is_string($alignment)) {
            return $alignment;
        }

        return VerticalAlignment::tryFrom($alignment) ?? $alignment;
    }

    public function getVerticalAlignmentClass(): ?string
    {
        $alignment = $this->getVerticalAlignment();

        if ($alignment instanceof VerticalAlignment) {
            return 'sy-vertical-align-' . $alignment->value;
        }

        return $alignment;
    }

    public function hasDynamicVerticalAlignment(): bool
    {
        return $this->verticalAlignment instanceof Closure;
    }
}
