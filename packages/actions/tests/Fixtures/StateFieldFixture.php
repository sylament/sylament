<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures;

use Sylament\Schemas\Components\Component;

/**
 * A minimal state-bearing leaf whose state path is its name — the actions-side
 * twin of schemas' StateField fixture, backing the schema-injection tests.
 */
class StateFieldFixture extends Component
{
    public static function make(string $name): static
    {
        return new static()->statePath($name);
    }
}
