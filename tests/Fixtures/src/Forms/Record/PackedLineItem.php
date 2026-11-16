<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms\Record;

/**
 * A value-object repeater item holding a nested repeater's items, as {@see LinePart}s or
 * as arrays.
 */
final readonly class PackedLineItem
{
    /**
     * @param list<mixed> $parts
     */
    public function __construct(
        public string $title,
        public array $parts = [],
    ) {
    }
}
