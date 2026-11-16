<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms\Record;

/**
 * A value-object repeater item: a title a field declares, and a note no field does.
 */
final readonly class LineItem
{
    public function __construct(
        public string $title,
        public ?string $note = null,
    ) {
    }
}
