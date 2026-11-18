<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListArticles;

/**
 * A resource with an index page that opts out of navigation; the index is the article list's
 * own, since only whether the resource is listed is read.
 */
final class UnlistedArticleResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static bool $shouldRegisterNavigation = false;

    public function getPages(): array
    {
        return ['index' => ListArticles::route('/')];
    }
}
