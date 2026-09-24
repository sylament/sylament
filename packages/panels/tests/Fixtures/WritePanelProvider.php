<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures;

use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Panels\Tests\Fixtures\Resources\AccessionResource;
use Sylament\Panels\Tests\Fixtures\Resources\PortfolioResource;
use Sylament\Panels\Tests\Fixtures\Resources\ReprintResource;
use Sylament\Panels\Tests\Fixtures\Resources\ShelfmarkResource;

/**
 * The write-path panel: a single identifier under each generator an edit form
 * can try to change, and a record whose relationships one form saves again
 * and again — apart from the `key` panel, whose resources are the composite
 * flavours.
 */
final class WritePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('write')
            ->path('write')
            ->pages([Dashboard::class])
            ->resources([
                ShelfmarkResource::class,
                AccessionResource::class,
                ReprintResource::class,
                PortfolioResource::class,
            ]);
    }
}
