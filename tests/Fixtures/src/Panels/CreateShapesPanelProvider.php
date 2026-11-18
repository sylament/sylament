<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Resources\AssignedSlugHeadlineResource;
use Sylament\Tests\Fixtures\Panels\Resources\CaselessBollardResource;
use Sylament\Tests\Fixtures\Panels\Resources\CrockCellarResource;
use Sylament\Tests\Fixtures\Panels\Resources\ListenerStampedBulletinResource;
use Sylament\Tests\Fixtures\Panels\Resources\LowercasedBadgeResource;
use Sylament\Tests\Fixtures\Panels\Resources\ManyToManySatchelResource;
use Sylament\Tests\Fixtures\Panels\Resources\MonumentPlaqueResource;
use Sylament\Tests\Fixtures\Panels\Resources\MonumentResource;
use Sylament\Tests\Fixtures\Panels\Resources\PottedPlantResource;
use Sylament\Tests\Fixtures\Panels\Resources\SecondManagerSpineResource;
use Sylament\Tests\Fixtures\Panels\Resources\ShelflessCrockResource;
use Sylament\Tests\Fixtures\Panels\Resources\UnkeyedLocketResource;
use Sylament\Tests\Fixtures\Panels\Resources\UnvettedKeyCharmResource;

/**
 * The create page's shapes: resources whose records the create page builds, keys, attaches and redirects differently.
 */
final class CreateShapesPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('create-shapes')
            ->path('create-shapes')
            ->resources([
                PottedPlantResource::class,
                LowercasedBadgeResource::class,
                CaselessBollardResource::class,
                SecondManagerSpineResource::class,
                UnkeyedLocketResource::class,
                AssignedSlugHeadlineResource::class,
                ManyToManySatchelResource::class,
                UnvettedKeyCharmResource::class,
                CrockCellarResource::class,
                ShelflessCrockResource::class,
                MonumentResource::class,
                MonumentPlaqueResource::class,
                ListenerStampedBulletinResource::class,
            ]);
    }
}
