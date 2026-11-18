<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditArticle;

/**
 * An article resource with a title attribute that switches global search off.
 */
final class OptedOutArticleSearchResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $pluralRecordLabel = 'opted-out articles';

    protected static bool $isGloballySearchable = false;

    public function getPages(): array
    {
        return [
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
