<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Resources\AllotmentResource;
use Sylament\Tests\Fixtures\Panels\Resources\GardenResource;
use Sylament\Tests\Fixtures\Panels\Resources\TerraceResource;
use Sylament\Tests\Fixtures\Panels\Resources\TwinBedGardenResource;

/**
 * The record pages that mount relation managers: a garden registering every kind of entry, a
 * terrace keyed by two scalars registering one manager, and an allotment keyed by its garden and
 * a number registering one group, and the garden again registering two groups that hold one
 * manager under one key.
 */
final class GardenRelationsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('relations')
            ->path('relations')
            ->resources([GardenResource::class, TerraceResource::class, AllotmentResource::class, TwinBedGardenResource::class]);
    }
}
