<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Article;

/**
 * Names the article's lone identifier field as its route key.
 */
final class IdKeyedArticleResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $recordRouteKeyName = 'id';
}
