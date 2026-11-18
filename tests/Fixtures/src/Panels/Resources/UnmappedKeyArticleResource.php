<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Article;

/**
 * Names as its route key a field the article does not map.
 */
final class UnmappedKeyArticleResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $recordRouteKeyName = 'slug';
}
