<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Components;

use Sylament\Schemas\Components\Component;

/**
 * A minimal state-bearing leaf: a component whose own state path is its name.
 * Stands in for the real form `Field` (the `forms` package is not yet ported) so
 * the deterministic state substrate can be exercised without a live round-trip.
 */
class StateField extends Component
{
    public static function make(string $name): static
    {
        return new static()->statePath($name);
    }
}
