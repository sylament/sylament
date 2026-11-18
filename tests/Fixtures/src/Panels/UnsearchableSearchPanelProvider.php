<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Pages\NotesPage;
use Sylament\Tests\Fixtures\Panels\Resources\OptedOutArticleSearchResource;
use Sylament\Tests\Fixtures\Panels\Resources\UntitledArticleSearchResource;

/**
 * A panel with global search on whose resources none can be searched, with a plain page to open it on.
 */
final class UnsearchableSearchPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('search-none')
            ->path('search-none')
            ->pages([NotesPage::class])
            ->resources([UntitledArticleSearchResource::class, OptedOutArticleSearchResource::class]);
    }
}
