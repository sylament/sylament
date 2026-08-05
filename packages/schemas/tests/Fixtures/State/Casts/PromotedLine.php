<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State\Casts;

final class PromotedLine
{
    public function __construct(
        public ?string $title = null,
        public ?string $note = null,
    ) {
    }
}
