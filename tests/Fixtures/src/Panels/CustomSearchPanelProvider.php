<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\GlobalSearch\ShelfGlobalSearchProvider;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\FetchJoinedGardenSearchResource;

/**
 * A panel whose search a custom provider answers, so the resources the default provider would walk are never searched:
 * among them one whose every search throws, which takes the default provider's whole search down here.
 */
final class CustomSearchPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('search-custom')
            ->path('search-custom')
            ->globalSearch(ShelfGlobalSearchProvider::class)
            ->resources([ArticleResource::class, FetchJoinedGardenSearchResource::class]);
    }
}
