<?php

declare(strict_types=1);

namespace Sylament\Support\Component\Concerns;

use Closure;
use Sylament\Support\Enum\FontWeight;

trait HasWeight
{
    protected FontWeight|string|Closure|null $weight = null;

    public function weight(FontWeight|string|Closure|null $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getWeight(): FontWeight|string|null
    {
        $weight = $this->evaluate($this->weight);

        if (!is_string($weight)) {
            return $weight;
        }

        return FontWeight::tryFrom($weight) ?? $weight;
    }

    /**
     * The class fragment templates emit for the weight: `sy-font-<value>` for the
     * enum cases, a custom string as-is, null when unset — so templates never
     * type-sniff the enum-vs-string union.
     */
    public function getWeightClass(): ?string
    {
        $weight = $this->getWeight();

        if ($weight instanceof FontWeight) {
            return 'sy-font-' . $weight->value;
        }

        return $weight;
    }

    public function hasWeight(): bool
    {
        return $this->weight !== null;
    }
}
