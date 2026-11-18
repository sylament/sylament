<?php

declare(strict_types=1);

use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Panel;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\HookedArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\OrderLineResource;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditArticle;

it('accumulates pages and resources across calls, keeping the first of a repeated entry, as a list', function (): void {
    $panel = Panel::make()
        ->pages([Dashboard::class, EditArticle::class])
        ->pages([Dashboard::class, CreateArticle::class])
        ->resources([ArticleResource::class, ArticleResource::class])
        ->resources([HookedArticleResource::class, ArticleResource::class, OrderLineResource::class]);

    expect($panel->getPages())->toBe([Dashboard::class, EditArticle::class, CreateArticle::class])
        ->and($panel->getResources())->toBe([ArticleResource::class, HookedArticleResource::class, OrderLineResource::class]);
});
