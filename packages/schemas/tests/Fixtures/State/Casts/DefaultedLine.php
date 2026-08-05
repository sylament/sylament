<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State\Casts;

final class DefaultedLine
{
    public function __construct(
        public ?string $note = 'n/a',
        public string $label = 'fallback',
    ) {
    }
}
