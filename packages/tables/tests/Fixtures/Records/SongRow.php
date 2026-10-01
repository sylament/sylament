<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Records;

/**
 * A row a `SELECT NEW` projection builds off the Doctrine `Song` — no entity,
 * so a query-lane table keys it by its readable `id`.
 */
final readonly class SongRow
{
    public function __construct(
        public int $id = 0,
        public string $title = '',
    ) {
    }
}
