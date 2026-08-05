<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State\Casts;

final class StrictLine
{
    public function __construct(
        public string $title,
    ) {
    }
}
