<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Sylament\Support\Enums\GridDirection;

trait HasGridDirection
{
    protected GridDirection|string|Closure|null $gridDirection = null;

    public function gridDirection(GridDirection|string|Closure|null $gridDirection): static
    {
        $this->gridDirection = $gridDirection;

        return $this;
    }

    public function getGridDirection(): ?GridDirection
    {
        $direction = $this->evaluate($this->gridDirection);

        if (is_string($direction)) {
            return GridDirection::tryFrom($direction);
        }

        return $direction;
    }
}
