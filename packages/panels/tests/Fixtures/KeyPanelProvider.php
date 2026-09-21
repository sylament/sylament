<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures;

use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Panels\Tests\Fixtures\Resources\CatalogueResource;
use Sylament\Panels\Tests\Fixtures\Resources\ColophonLineResource;
use Sylament\Panels\Tests\Fixtures\Resources\ColophonResource;
use Sylament\Panels\Tests\Fixtures\Resources\EditionResource;
use Sylament\Panels\Tests\Fixtures\Resources\FlatStampResource;
use Sylament\Panels\Tests\Fixtures\Resources\ImprintResource;
use Sylament\Panels\Tests\Fixtures\Resources\SeparatorProbeResource;
use Sylament\Panels\Tests\Fixtures\Resources\StampMarkResource;
use Sylament\Panels\Tests\Fixtures\Resources\StampResource;

/**
 * The composite-identifier panel: every identifier flavour the epic keys, the
 * two shapes it still refuses, and the separator paths the route loader has to
 * mount. Deliberately not the default one — the enumerating assertions of the
 * navigation and global-search suites name `admin` and `nest`, and a second
 * `default()` would silently reorder the panel they resolve.
 */
final class KeyPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('key')
            ->path('key')
            ->pages([Dashboard::class])
            ->resources([
                ImprintResource::class,
                CatalogueResource::class,
                StampResource::class,
                StampMarkResource::class,
                FlatStampResource::class,
                ColophonResource::class,
                ColophonLineResource::class,
                EditionResource::class,
                SeparatorProbeResource::class,
            ]);
    }
}
