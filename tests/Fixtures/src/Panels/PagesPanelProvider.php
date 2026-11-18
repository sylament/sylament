<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Support\Enums\Width;
use Sylament\Tests\Fixtures\Panels\Pages\CustomShellPage;
use Sylament\Tests\Fixtures\Panels\Pages\MonthlyReportPage;
use Sylament\Tests\Fixtures\Panels\Pages\NotesPage;
use Sylament\Tests\Fixtures\Panels\Pages\SettingsPage;
use Sylament\Tests\Fixtures\Panels\Pages\StandalonePage;
use Sylament\Tests\Fixtures\Panels\Pages\TemplatedPage;

/**
 * A panel of plain pages and no resources, each page one shape a user writes, with a content
 * width of its own for a page's width to win over.
 */
final class PagesPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('pages')
            ->path('pages')
            ->maxContentWidth(Width::FiveExtraLarge)
            ->pages([NotesPage::class, SettingsPage::class, TemplatedPage::class, MonthlyReportPage::class, StandalonePage::class, CustomShellPage::class]);
    }
}
