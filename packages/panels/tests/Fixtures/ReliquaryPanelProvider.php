<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures;

use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Panels\Tests\Fixtures\Resources\RelicResource;
use Sylament\Panels\Tests\Fixtures\Resources\ReliquaryResource;

/**
 * The reliquary panel: a nested create under a many-to-many parent whose
 * refusals must hand the record back. Not the default panel, for the reason
 * {@see KeyPanelProvider} gives.
 */
final class ReliquaryPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('reliquary')
            ->path('reliquary')
            ->pages([Dashboard::class])
            ->resources([ReliquaryResource::class, RelicResource::class]);
    }
}
