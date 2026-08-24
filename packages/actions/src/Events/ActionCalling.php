<?php

declare(strict_types=1);

namespace Sylament\Actions\Events;

use Sylament\Actions\Action;

final readonly class ActionCalling
{
    public function __construct(
        private Action $action,
    ) {
    }

    public function getAction(): Action
    {
        return $this->action;
    }
}
