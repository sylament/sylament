<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms\Record;

/**
 * The value-object item of a repeater nested in a {@see PackedLineItem}.
 */
final readonly class LinePart
{
    public function __construct(
        public string $label,
    ) {
    }
}
