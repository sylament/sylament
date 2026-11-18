<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Resources\AllotmentResource;
use Sylament\Tests\Fixtures\Panels\Resources\AllotmentSeedlingResource;
use Sylament\Tests\Fixtures\Panels\Resources\ArrivingVoyageResource;
use Sylament\Tests\Fixtures\Panels\Resources\DockResource;
use Sylament\Tests\Fixtures\Panels\Resources\GardenPlantResource;
use Sylament\Tests\Fixtures\Panels\Resources\GardenResource;
use Sylament\Tests\Fixtures\Panels\Resources\GardenSeedlingResource;
use Sylament\Tests\Fixtures\Panels\Resources\GreenhouseVentResource;
use Sylament\Tests\Fixtures\Panels\Resources\GreenhouseVentSeedlingResource;
use Sylament\Tests\Fixtures\Panels\Resources\LarderBinNoteResource;
use Sylament\Tests\Fixtures\Panels\Resources\LarderBinResource;
use Sylament\Tests\Fixtures\Panels\Resources\StockedLarderResource;
use Sylament\Tests\Fixtures\Panels\Resources\TerraceResource;
use Sylament\Tests\Fixtures\Panels\Resources\TerraceSeedlingResource;

/**
 * Resources nested under a parent: a garden's seedlings and plants, a larder's bins and their
 * notes three levels deep, seedlings under a terrace, an allotment and a greenhouse vent, one
 * parent of each key width, and a dock's arrivals under a dock listing nothing.
 */
final class NestedPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('nested')
            ->path('nested')
            ->resources([
                GardenResource::class,
                GardenSeedlingResource::class,
                GardenPlantResource::class,
                StockedLarderResource::class,
                LarderBinResource::class,
                LarderBinNoteResource::class,
                TerraceResource::class,
                TerraceSeedlingResource::class,
                AllotmentResource::class,
                AllotmentSeedlingResource::class,
                GreenhouseVentResource::class,
                GreenhouseVentSeedlingResource::class,
                DockResource::class,
                ArrivingVoyageResource::class,
            ]);
    }
}
