<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Pages\OverviewDashboard;
use Sylament\Tests\Fixtures\Panels\Pages\UnlistedFaultyPage;
use Sylament\Tests\Fixtures\Panels\Pages\YearEndReportPage;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\UnlistedArticleResource;

/**
 * A branded panel with non-collapsible groups whose navigation comes from every source: pages,
 * resources and the panel's own items, sorted across sources; a group registered by its label,
 * one registered under a key with another label, one nothing fills, and a bucket no
 * registration names; a hidden item, and a page and a resource that opt out.
 */
final class BrandedNavigationPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('panel-branded')
            ->path('panel-branded')
            ->brandName('Acme')
            ->brandLogo('/brand/acme.svg')
            ->darkModeBrandLogo('/brand/acme-dark.svg')
            ->brandLogoHeight('2rem')
            ->collapsibleNavigationGroups(false)
            ->pages([OverviewDashboard::class, YearEndReportPage::class, UnlistedFaultyPage::class])
            ->resources([ArticleResource::class, UnlistedArticleResource::class])
            ->navigationGroups(['Reports', 'library' => NavigationGroup::make('Library'), 'Archive'])
            ->navigationItems([
                NavigationItem::make('Glossary')->group('Misc')->sort(-6)->url('/glossary'),
                NavigationItem::make('Catalogue')->group('library')->sort(-4)->url('/catalogue'),
                NavigationItem::make('Pinned')->sort(-3)->url('/pinned'),
                NavigationItem::make('Quarterly')->group('Reports')->sort(3)->url('/quarterly'),
                NavigationItem::make('Secret')->visible(false)->url('/secret'),
            ]);
    }
}
