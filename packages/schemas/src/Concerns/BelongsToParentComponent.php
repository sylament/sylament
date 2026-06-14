<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Sylament\Schemas\Components\Component;

trait BelongsToParentComponent
{
    protected ?Component $parentComponent = null;

    public function parentComponent(?Component $parentComponent): static
    {
        $this->parentComponent = $parentComponent;

        return $this;
    }

    public function getParentComponent(): ?Component
    {
        return $this->parentComponent;
    }
}
