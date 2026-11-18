<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\OrderLine;

/**
 * Names one field of the two its record is identified by as its route key.
 */
final class OrderKeyedLineResource extends Resource
{
    protected static ?string $recordClass = OrderLine::class;

    protected static ?string $recordRouteKeyName = 'order';
}
