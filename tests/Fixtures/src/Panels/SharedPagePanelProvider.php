<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Pages\NotesPage;

/**
 * A second panel listing a page {@see PagesPanelProvider} lists too.
 */
final class SharedPagePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('pages-shared')
            ->path('pages-shared')
            ->pages([NotesPage::class]);
    }
}
