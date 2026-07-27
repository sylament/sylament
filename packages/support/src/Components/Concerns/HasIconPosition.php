<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;
use Sylament\Support\Enums\IconPosition;

use function Sylament\Support\blank;

trait HasIconPosition
{
    protected IconPosition|string|Closure|null $iconPosition = null;

    public function iconPosition(IconPosition|string|Closure|null $position): static
    {
        $this->iconPosition = $position;

        return $this;
    }

    public function getIconPosition(): IconPosition
    {
        $position = $this->evaluate($this->iconPosition);

        if ($position instanceof IconPosition) {
            return $position;
        }

        if (blank($position)) {
            return IconPosition::Before;
        }

        return IconPosition::tryFrom($position) ?? IconPosition::Before;
    }

    public function hasIconPosition(): bool
    {
        return $this->iconPosition !== null;
    }
}
