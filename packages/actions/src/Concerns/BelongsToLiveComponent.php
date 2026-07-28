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
        return $this->getLiveComponentOrNull()
            ?? throw new RuntimeException('Action is not part of LiveComponent');
    }

    public function getLiveComponentOrNull(): ?InteractsWithActions
    {
        return $this->liveComponent
            ?? self::asActionsHost($this->getSchemaContainer()?->getLiveComponentOrNull())
            ?? self::asActionsHost($this->getSchemaComponent()?->getLiveComponentOrNull());
    }

    private static function asActionsHost(?object $host): ?InteractsWithActions
    {
        return $host instanceof InteractsWithActions ? $host : null;
    }
}
