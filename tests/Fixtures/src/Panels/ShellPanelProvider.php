<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Pages\ActiveIconShelfPage;
use Sylament\Tests\Fixtures\Panels\Pages\SluggedSettingsPage;

/**
 * A panel with no brand and no resource, so no global search, whose sidebar holds every group
 * and item shape the shell draws: an unlabelled group, a collapsible group with an icon, a
 * collapsed group and a static one; a page with an active icon, badges of `0` and of a
 * colour, and an item opening in a new tab.
 */
final class ShellPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('shell')
            ->path('shell')
            ->pages([ActiveIconShelfPage::class, SluggedSettingsPage::class])
            ->navigationGroups([
                NavigationGroup::make('Library')->icon('support:star-lg'),
                NavigationGroup::make('Archive')->collapsed(),
                NavigationGroup::make('Help')->collapsible(false),
            ])
            ->navigationItems([
                NavigationItem::make('Inbox')->badge('0')->url('/inbox'),
                NavigationItem::make('Alerts')->group('Archive')->badge('3', color: 'danger')->url('/alerts'),
                NavigationItem::make('Handbook')->group('Help')->url('https://example.com/handbook', shouldOpenInNewTab: true),
            ]);
    }
}
