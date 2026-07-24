<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use RuntimeException;
use Sylament\Actions\Contracts\InteractsWithActions;

trait BelongsToLiveComponent
{
    protected ?InteractsWithActions $liveComponent = null;

    public function liveComponent(?InteractsWithActions $liveComponent = null): static
    {
        $this->liveComponent = $liveComponent;

        return $this;
    }

    public function getLiveComponent(): InteractsWithActions
    {
        if ($this->liveComponent === null) {
            throw new RuntimeException('Action is not part of LiveComponent');
        }

        return $this->liveComponent;
    }
}
