<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Sylament\Schemas\Contracts\InteractsWithSchemas;

trait BelongsToLiveComponent
{
    protected ?InteractsWithSchemas $liveComponent = null;

    public function liveComponent(?InteractsWithSchemas $liveComponent = null): static
    {
        $this->liveComponent = $liveComponent;

        return $this;
    }

    public function getLiveComponent(): ?InteractsWithSchemas
    {
        return $this->liveComponent;
    }
}
