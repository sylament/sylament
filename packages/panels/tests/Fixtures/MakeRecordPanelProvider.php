<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures;

use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Panels\Tests\Fixtures\Resources\CountermarkResource;
use Sylament\Panels\Tests\Fixtures\Resources\MillResource;
use Sylament\Panels\Tests\Fixtures\Resources\WatermarkResource;

/**
 * The construction panel: records whose constructor takes their key — a scalar
 * pair, and a derived identity whose parent half the constructor sets — so a
 * create page builds them in makeRecord(). Not the default panel, for the
 * reason {@see KeyPanelProvider} gives.
 */
final class MakeRecordPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('make')
            ->path('make')
            ->pages([Dashboard::class])
            ->resources([
                WatermarkResource::class,
                MillResource::class,
                CountermarkResource::class,
            ]);
    }
}
