<?php

declare(strict_types=1);

namespace Sylament\Panels\Panel\Concerns;

use Closure;
use Sylament\Panels\Pages\Enums\SubNavigationPosition;

trait HasSubNavigation
{
    protected SubNavigationPosition|Closure|null $subNavigationPosition = null;

    public function subNavigationPosition(SubNavigationPosition|Closure|null $position): static
    {
        $this->subNavigationPosition = $position;

        return $this;
    }

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return $this->evaluate($this->subNavigationPosition) ?? SubNavigationPosition::Start;
    }
}
