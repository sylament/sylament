<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;
use Sylament\Support\Enums\Alignment;

trait HasAlignment
{
    protected Alignment|string|Closure|null $alignment = null;

    public function alignment(Alignment|string|Closure|null $alignment): static
    {
        $this->alignment = $alignment;

        return $this;
    }

    public function alignStart(bool|Closure $condition = true): static
    {
        return $this->alignment(fn (): ?Alignment => $this->evaluate($condition) ? Alignment::Start : null);
    }

    public function alignCenter(bool|Closure $condition = true): static
    {
        return $this->alignment(fn (): ?Alignment => $this->evaluate($condition) ? Alignment::Center : null);
    }

    public function alignEnd(bool|Closure $condition = true): static
    {
        return $this->alignment(fn (): ?Alignment => $this->evaluate($condition) ? Alignment::End : null);
    }

    public function alignJustify(bool|Closure $condition = true): static
    {
        return $this->alignment(fn (): ?Alignment => $this->evaluate($condition) ? Alignment::Justify : null);
    }

    public function alignBetween(bool|Closure $condition = true): static
    {
        return $this->alignment(fn (): ?Alignment => $this->evaluate($condition) ? Alignment::Between : null);
    }

    public function alignLeft(bool|Closure $condition = true): static
    {
        return $this->alignment(fn (): ?Alignment => $this->evaluate($condition) ? Alignment::Left : null);
    }

    public function alignRight(bool|Closure $condition = true): static
    {
        return $this->alignment(fn (): ?Alignment => $this->evaluate($condition) ? Alignment::Right : null);
    }

    public function getAlignment(): Alignment|string|null
    {
        $alignment = $this->evaluate($this->alignment);

        if (!is_string($alignment)) {
            return $alignment;
        }

        return Alignment::tryFrom($alignment) ?? $alignment;
    }

    /**
     * The class fragment templates emit for the alignment: `sy-align-<value>`
     * for the enum cases, a custom string as-is, null when unaligned — so
     * templates never type-sniff the enum-vs-string union.
     */
    public function getAlignmentClass(): ?string
    {
        $alignment = $this->getAlignment();

        if ($alignment instanceof Alignment) {
            return 'sy-align-' . $alignment->value;
        }

        return $alignment;
    }

    public function hasDynamicAlignment(): bool
    {
        return $this->alignment instanceof Closure;
    }
}
