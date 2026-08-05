<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State\Casts;

final class NestedLine
{
    public InnerLine $inner;

    public function __construct(
        public ?string $title = null,
    ) {
        $this->inner = new InnerLine();
    }
}
