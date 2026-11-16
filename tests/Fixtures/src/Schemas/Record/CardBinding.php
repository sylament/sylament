<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Record;

/**
 * A nested object the mapper cannot build, since its constructor requires an argument.
 */
class CardBinding
{
    public function __construct(
        public string $material,
    ) {
    }
}
