<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Components;

/**
 * A hand-built stand-in for the forms Repeater: a state-bearing component that
 * nests path-bearing children and still declares its own value the record value
 * for everything beneath its path, so the record walks treat it as a leaf.
 */
final class SubtreeOwner extends StateField
{
    public function ownsRecordSubtree(): bool
    {
        return true;
    }
}
