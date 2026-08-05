<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Records;

final class LineItem
{
    public function __construct(
        public ?string $title = null,
        public ?string $note = null,
    ) {
    }
}
