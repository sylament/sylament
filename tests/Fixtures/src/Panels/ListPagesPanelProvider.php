<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Resources\ModalArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\RestampedArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\TabbedArticleResource;

/**
 * A panel of article lists configured beyond the admin panel's plain one: a tabbed, browsable list, a list
 * whose resource registers no other page, and a list whose resource re-stamps its own table.
 */
final class ListPagesPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('list-pages')
            ->path('list-pages')
            ->resources([TabbedArticleResource::class, ModalArticleResource::class, RestampedArticleResource::class]);
    }
}
