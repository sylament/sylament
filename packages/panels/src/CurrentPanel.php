<?php

declare(strict_types=1);

namespace Sylament\Panels;

use LogicException;
use Symfony\Component\HttpFoundation\RequestStack;

class CurrentPanel
{
    public function __construct(
        private readonly RequestStack $requests,
        private readonly PanelRegistry $registry,
    ) {
    }

    public function get(): ?Panel
    {
        $id = $this->requests->getCurrentRequest()?->attributes->get('_sylament_panel');

        if ($id === null) {
            return null;
        }

        if (! is_string($id)) {
            throw new LogicException('The `_sylament_panel` request attribute must be a panel id string.');
        }

        return $this->registry->get($id)
            ?? throw new LogicException(sprintf('The request carries panel "%s" but no such panel is registered.', $id));
    }
}
