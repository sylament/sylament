<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State;

final class PostTag
{
    public function __construct(
        public string $name = '',
    ) {
    }
}
