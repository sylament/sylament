<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Tests\Fixtures\Panels\Resources\BandedOrderLineResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Registered as the resource's view page, which `ViewRecord` (not ported) would be: a record page under its own URL.
 */
#[AsLiveComponent]
final class ViewBandedOrderLine extends EditRecord
{
    protected static string $resource = BandedOrderLineResource::class;
}
