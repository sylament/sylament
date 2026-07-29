<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use RuntimeException;
use Sylament\Schemas\Contracts\InteractsWithSchemas;

trait BelongsToLiveComponent
{
    protected ?InteractsWithSchemas $liveComponent = null;

    public function liveComponent(?InteractsWithSchemas $liveComponent = null): static
    {
        $this->liveComponent = $liveComponent;

        return $this;
    }

    public function getLiveComponent(): InteractsWithSchemas
    {
        if ($this->liveComponent === null) {
            throw new RuntimeException('Schema is not part of LiveComponent');
        }

        return $this->liveComponent;
    }

    public function getLiveComponentOrNull(): ?InteractsWithSchemas
    {
        return $this->liveComponent;
    }
}
