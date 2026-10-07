<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Architecture\Trail;

/**
 * One Surface or Unported row. Cells keep their markdown, escaped pipes included; an Unported row has
 * no Ports column, so its $ports is ''.
 */
final readonly class Row
{
    public function __construct(
        public string $feature,
        public string $ports,
        public Status $status,
        public string $blockedBy,
        public string $detail,
        public int $line,
    ) {
    }
}
