<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Twig\Markup;

trait HasTooltips
{
    /**
     * @var array<string|int, string|Markup>|Closure|null
     */
    protected array|Closure|null $tooltips = null;

    /**
     * @var ?array<string|int, string|Markup>
     */
    protected ?array $cachedTooltips = null;

    protected bool $hasCachedTooltips = false;

    /**
     * @param array<string|int, string|Markup>|Closure|null $tooltips
     */
    public function tooltips(array|Closure|null $tooltips): static
    {
        $this->tooltips = $tooltips;

        $this->cachedTooltips = null;
        $this->hasCachedTooltips = false;

        return $this;
    }

    public function getTooltip(string|int $value): string|Markup|null
    {
        return $this->getTooltips()[$value] ?? null;
    }

    /**
     * @return ?array<string|int, string|Markup>
     */
    public function getTooltips(): ?array
    {
        if ($this->hasCachedTooltips) {
            return $this->cachedTooltips;
        }

        $this->cachedTooltips = $this->evaluate($this->tooltips);
        $this->hasCachedTooltips = true;

        return $this->cachedTooltips;
    }
}
