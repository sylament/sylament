<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Pages\BandedPlainPage;
use Sylament\Tests\Fixtures\Panels\Pages\TopBandedPlainPage;

/**
 * A panel that names no sub-navigation position, showing the banded plain pages again.
 */
final class StartBandPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('navigation-start')
            ->path('navigation-start')
            ->pages([BandedPlainPage::class, TopBandedPlainPage::class]);
    }
}
