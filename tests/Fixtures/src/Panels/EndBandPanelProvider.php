<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Pages\Enums\SubNavigationPosition;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Pages\BandedPlainPage;
use Sylament\Tests\Fixtures\Panels\Pages\SluggedSettingsPage;
use Sylament\Tests\Fixtures\Panels\Pages\TopBandedPlainPage;
use Sylament\Tests\Fixtures\Panels\Resources\BandedArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\TopBandedArticleResource;

/**
 * A panel that draws a sub-navigation band at the end, unless a page or a resource names another
 * position: two plain pages with a band, one without, and two article resources whose record
 * pages draw one.
 */
final class EndBandPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('navigation-band')
            ->path('navigation-band')
            ->subNavigationPosition(SubNavigationPosition::End)
            ->pages([BandedPlainPage::class, TopBandedPlainPage::class, SluggedSettingsPage::class])
            ->resources([BandedArticleResource::class, TopBandedArticleResource::class]);
    }
}
