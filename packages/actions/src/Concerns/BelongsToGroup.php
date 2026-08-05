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

    public function getRootGroup(): ?ActionGroup
    {
        $group = $this->getGroup();

        while ($group !== null) {
            $parentGroup = $group->getGroup();

            if (!$parentGroup instanceof ActionGroup) {
                break;
            }

            $group = $parentGroup;
        }

        return $group;
    }
}
