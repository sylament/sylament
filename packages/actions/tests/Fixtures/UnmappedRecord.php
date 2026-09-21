<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures;

/**
 * A named class no Doctrine manager maps, which is what an action over a plain
 * data object meets: nothing describes an identity, so the property literally
 * named `id` is the only one there is.
 */
final class UnmappedRecord
{
    public int $id = 9;
}
