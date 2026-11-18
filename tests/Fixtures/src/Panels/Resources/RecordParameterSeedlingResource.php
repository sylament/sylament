<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Seedling;

/**
 * A garden's seedlings whose garden travels under the parameter `record`, registered on no
 * kernel panel.
 */
final class RecordParameterSeedlingResource extends Resource
{
    protected static ?string $recordClass = Seedling::class;

    protected static ?string $parentResource = GardenResource::class;

    public function getParentRouteParameterName(): string
    {
        return 'record';
    }
}
