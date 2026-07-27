<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Sylament\Schemas\Contracts\InteractsWithSchemas;
use RuntimeException;

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

    /**
     * The nullable twin of {@see getLiveComponent()}, for callers probing an
     * optional binding (the action-side live-component chain) rather than
     * requiring one.
     */
    public function getLiveComponentOrNull(): ?InteractsWithSchemas
    {
        return $this->liveComponent;
    }
}
