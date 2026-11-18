<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Article;

/**
 * A searched article with no page at all, so a hit links nowhere.
 */
final class UnroutedArticleSearchResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $pluralRecordLabel = 'unrouted articles';
}
