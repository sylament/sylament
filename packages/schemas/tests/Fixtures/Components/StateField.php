<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Components;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Contracts\HasValidationConstraints;

/**
 * A minimal state-bearing leaf: a component whose own state path is its name.
 * Stands in for the real form `Field` (the `forms` package is not yet ported) so
 * the deterministic state substrate can be exercised without a live round-trip
 * — declaring, as that field does, that it shows its own errors, though it
 * carries no constraints.
 */
class StateField extends Component implements HasValidationConstraints
{
    public static function make(string $name): static
    {
        return new static()->statePath($name);
    }

    public function getValidationConstraints(): array
    {
        return [];
    }

    public function dehydrateValidationConstraints(array &$constraints): void
    {
    }
}
