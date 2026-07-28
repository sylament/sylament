<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

trait CanCallParentAction
{
    protected ?string $parentActionCallLiveComponentClickHandler = null;

    public function callParent(?string $handler): static
    {
        $this->parentActionCallLiveComponentClickHandler = $handler;

        return $this;
    }

    public function getParentActionCallLiveComponentClickHandler(): ?string
    {
        return $this->parentActionCallLiveComponentClickHandler;
    }
}
