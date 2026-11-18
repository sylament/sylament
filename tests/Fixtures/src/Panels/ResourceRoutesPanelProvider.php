<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleArchiveResource;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\NoIndexResource;
use Sylament\Tests\Fixtures\Panels\Resources\ScopedArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\TitleKeyedArticleResource;

/**
 * A second panel mounting the admin panel's articles beside resources no other panel lists, so a URL
 * minted for one of them answers for this panel or for none.
 */
final class ResourceRoutesPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('resource-routes')
            ->path('resource-routes')
            ->resources([ArticleResource::class, ScopedArticleResource::class, TitleKeyedArticleResource::class, NoIndexResource::class, ArticleArchiveResource::class]);
    }
}
