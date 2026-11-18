<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditArticle;

/**
 * An article resource naming no record title attribute, so nothing to search by.
 */
final class UntitledArticleSearchResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $pluralRecordLabel = 'untitled articles';

    public function getPages(): array
    {
        return [
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
