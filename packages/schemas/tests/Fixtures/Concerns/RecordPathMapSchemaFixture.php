<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;

/**
 * Exposes the record path map both mapping walks share, so its shape can be
 * asserted directly instead of inferred from a fill/getData round-trip.
 */
final class RecordPathMapSchemaFixture extends Schema
{
    /**
     * @return array<string, Component>
     */
    public function recordPathMap(): array
    {
        return $this->getRecordPathMap();
    }
}
