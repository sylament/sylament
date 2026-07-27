<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Sylament\Actions\ActionGroup;

trait BelongsToGroup
{
    protected ?ActionGroup $group = null;

    public function group(?ActionGroup $group = null): static
    {
        $this->group = $group;

        return $this;
    }

    public function getGroup(): ?ActionGroup
    {
        return $this->group;
    }
}
