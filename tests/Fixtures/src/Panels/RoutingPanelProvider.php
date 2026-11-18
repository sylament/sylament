<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Pages\AttributedSignInPage;
use Sylament\Tests\Fixtures\Panels\Pages\MistypedWidthPage;
use Sylament\Tests\Fixtures\Panels\Pages\SluggedSettingsPage;
use Sylament\Tests\Fixtures\Panels\Pages\ThirdLayoutPage;
use Sylament\Tests\Fixtures\Panels\Resources\SeparatedPathResource;

/**
 * A plain panel, with no brand and collapsible groups: the shipped dashboard at its root, a
 * page at a slug, a simple page, the two pages the controller refuses, a labelled group of
 * one panel item, and a resource whose pages a separator follows their record placeholder.
 */
final class RoutingPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('panel-routing')
            ->path('panel-routing')
            ->pages([SluggedSettingsPage::class, Dashboard::class, AttributedSignInPage::class, ThirdLayoutPage::class, MistypedWidthPage::class])
            ->navigationItems([NavigationItem::make('Changelog')->group('Help')->url('/changelog')])
            ->resources([SeparatedPathResource::class]);
    }
}
