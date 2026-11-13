<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\HookedArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\OrderLineResource;

/**
 * The default panel: a live page built outside a panel route answers for it.
 */
final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->default()
            ->resources([ArticleResource::class, HookedArticleResource::class, OrderLineResource::class]);
    }
}
