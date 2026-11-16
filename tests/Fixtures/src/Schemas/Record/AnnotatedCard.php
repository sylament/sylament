<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Record;

/**
 * A plain record whose array-valued branch may be null, the shape a component owning
 * its record subtree writes whole.
 */
class AnnotatedCard
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $notes = null;
}
