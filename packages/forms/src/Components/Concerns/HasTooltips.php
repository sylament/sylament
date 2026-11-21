<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Markup;

trait HasTooltips
{
    /**
     * @var array<string|int, string|Markup|TranslatableInterface>|Closure|null
     */
    protected array|Closure|null $tooltips = null;

    /**
     * @var ?array<string|int, string|Markup>
     */
    protected ?array $cachedTooltips = null;

    protected bool $hasCachedTooltips = false;

    /**
     * @param array<string|int, string|Markup|TranslatableInterface>|Closure|null $tooltips
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

        $tooltips = $this->evaluate($this->tooltips);

        if ($tooltips !== null) {
            $translated = [];

            foreach ($tooltips as $value => $tooltip) {
                $translated[$value] = $tooltip instanceof TranslatableInterface ? $tooltip->trans($this->getTranslator()) : $tooltip;
            }

            $tooltips = $translated;
        }

        $this->cachedTooltips = $tooltips;
        $this->hasCachedTooltips = true;

        return $this->cachedTooltips;
    }
}
