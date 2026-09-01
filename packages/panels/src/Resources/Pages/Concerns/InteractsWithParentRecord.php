<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Pages\Concerns;

use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * The records a nested page hangs under: their route keys on the wire, root
 * first, and the chain they resolve to.
 */
trait InteractsWithParentRecord
{
    /**
     * The ancestor route keys, keyed by the parameter each travels under and
     * ordered root-first — keys, never records, exactly as {@see $record} is.
     *
     * @var array<string, string>
     */
    #[LiveProp]
    public array $parentRecords = [];
}
