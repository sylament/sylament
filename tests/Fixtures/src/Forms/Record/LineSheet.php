<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms\Record;

/**
 * A record whose repeated lines are a non-nullable array.
 */
final class LineSheet
{
    /**
     * @var list<mixed>
     */
    private array $lines = [];

    /**
     * @return list<mixed>
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    /**
     * @param list<mixed> $lines
     */
    public function setLines(array $lines): void
    {
        $this->lines = $lines;
    }
}
