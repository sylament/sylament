<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables\Record;

use Stringable;

/**
 * A row a table lists that is no entity: a name and the `id` it is keyed by, which may be
 * an integer, a `Stringable` such as a uuid, or missing.
 */
final class ShelfItem
{
    public function __construct(
        public string $name,
        public int|string|Stringable|null $id = null,
    ) {
    }
}
