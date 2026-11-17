<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms\Record;

/**
 * A record that keeps its publication stamp as a string, so a date field writes it the
 * string its format spells rather than a date object.
 */
final class StampedNote
{
    public function __construct(
        public ?string $publishedAt = null,
    ) {
    }
}
