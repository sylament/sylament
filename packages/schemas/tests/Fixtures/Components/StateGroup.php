<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Components;

use Sylament\Schemas\Components\Component;

/**
 * A minimal layout wrapper for state tests: a component that nests child
 * components (via the inherited `components()` / `childComponents()`), standing
 * in for a real layout like `Section` so the dehydration walk's nesting branches
 * can be exercised kernel-free. Left path-less it is a pure wrapper (its children
 * bind at the container path); given a `->statePath(...)` it becomes a
 * path-bearing group that can share a path with a sibling.
 */
class StateGroup extends Component
{
    public static function make(): static
    {
        return new static();
    }
}
