<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;

/**
 * A panel with global search switched off over a resource that could be searched.
 */
final class QuietSearchPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('search-off')
            ->path('search-off')
            ->globalSearch(false)
            ->resources([ArticleResource::class]);
    }
}
