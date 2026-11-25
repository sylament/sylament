<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Resources\ObservatoryResource;

/**
 * A resource over a record class only the `secondary` manager maps, on a panel of its own so no other panel's
 * navigation, listing or count changes.
 */
final class SecondaryManagerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('secondary-manager')
            ->path('secondary-manager')
            ->resources([ObservatoryResource::class]);
    }
}
