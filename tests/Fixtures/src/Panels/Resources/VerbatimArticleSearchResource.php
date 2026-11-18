<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditArticle;

/**
 * A searched article whose term is searched whole, never split into words, with case folding forced off; it sorts
 * after the panel's other resources.
 */
final class VerbatimArticleSearchResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $pluralRecordLabel = 'verbatim articles';

    protected static ?bool $shouldSplitGlobalSearchTerms = false;

    protected static ?bool $isGlobalSearchForcedCaseInsensitive = false;

    protected static ?int $globalSearchSort = 1;

    public function getPages(): array
    {
        return [
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
