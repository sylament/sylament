<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Architecture\Trail;

/**
 * One Surface or Unported table: its rows without the unaudited one, which only sets $unaudited.
 * $section is the heading text after "## ", and $line is the header row's line.
 */
final readonly class Table
{
    /**
     * @param list<Row> $rows
     */
    public function __construct(
        public string $section,
        public array $rows,
        public bool $unaudited,
        public int $line,
    ) {
    }
}
