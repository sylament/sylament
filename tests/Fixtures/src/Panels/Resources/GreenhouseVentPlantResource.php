<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Plant;

/**
 * A vent's plants, a many-to-many no field on the plant completes, as a resource nested under
 * the greenhouse vent's, registered on no panel.
 */
final class GreenhouseVentPlantResource extends Resource
{
    protected static ?string $recordClass = Plant::class;

    protected static ?string $parentResource = GreenhouseVentResource::class;
}
