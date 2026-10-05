<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures;

use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Panels\Tests\Fixtures\Resources\LocketResource;

/**
 * The locket panel: a create page whose record's whole key flattens to `''`.
 * Not the default panel, for the reason {@see KeyPanelProvider} gives.
 */
final class LocketPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('locket')
            ->path('locket')
            ->pages([Dashboard::class])
            ->resources([LocketResource::class]);
    }
}
