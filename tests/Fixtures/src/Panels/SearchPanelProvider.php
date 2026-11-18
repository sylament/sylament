<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Tests\Fixtures\Panels\Resources\AnnotatedArticleSearchResource;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\CuratedArticleSearchResource;
use Sylament\Tests\Fixtures\Panels\Resources\OptedOutArticleSearchResource;
use Sylament\Tests\Fixtures\Panels\Resources\SeedlingGardenSearchResource;
use Sylament\Tests\Fixtures\Panels\Resources\UnroutedArticleSearchResource;
use Sylament\Tests\Fixtures\Panels\Resources\UntitledArticleSearchResource;
use Sylament\Tests\Fixtures\Panels\Resources\VerbatimArticleSearchResource;

/**
 * A panel searching through the default provider, its field tuned by every knob the panel offers, over one resource
 * per search shape a resource configures: the plain one, and those that sort, annotate, curate, keep the term whole,
 * search two attributes, link nowhere, name no attribute and opt out.
 */
final class SearchPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('search')
            ->path('search')
            ->globalSearchDebounce('1s')
            ->globalSearchKeyBindings(['mod+k', 'ctrl+/'])
            ->globalSearchFieldSuffix('Ctrl K')
            ->resources([
                ArticleResource::class,
                AnnotatedArticleSearchResource::class,
                CuratedArticleSearchResource::class,
                VerbatimArticleSearchResource::class,
                SeedlingGardenSearchResource::class,
                UnroutedArticleSearchResource::class,
                UntitledArticleSearchResource::class,
                OptedOutArticleSearchResource::class,
            ]);
    }
}
