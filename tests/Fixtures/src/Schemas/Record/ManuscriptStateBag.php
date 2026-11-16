<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Record;

/**
 * A host's state bag by shape alone: the public array a live host keeps its state in, with no host around it.
 */
final class ManuscriptStateBag
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public array $data = [],
    ) {
    }
}
