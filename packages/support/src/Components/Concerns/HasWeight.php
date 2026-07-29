<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;
use Sylament\Support\Enums\FontWeight;

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
