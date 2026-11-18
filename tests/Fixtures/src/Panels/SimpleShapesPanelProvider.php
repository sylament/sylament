<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Pages\CardSimplePage;
use Sylament\Tests\Fixtures\Panels\Pages\NotesPage;

/**
 * A panel that sets none of the knobs a simple page reads: no brand, no content width and
 * no unsaved-changes alert, beside an ordinary page to compare the shells by.
 */
final class SimpleShapesPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('simple-shapes')
            ->path('simple-shapes')
            ->pages([CardSimplePage::class, NotesPage::class]);
    }
}
