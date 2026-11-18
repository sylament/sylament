<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;

/**
 * Names an association as its route key.
 */
final class InvoiceKeyedLineResource extends Resource
{
    protected static ?string $recordClass = InvoiceLine::class;

    protected static ?string $recordRouteKeyName = 'invoice';
}
