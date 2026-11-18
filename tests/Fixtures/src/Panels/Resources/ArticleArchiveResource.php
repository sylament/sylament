<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use BackedEnum;
use Sylament\Panels\Resources\Resource;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ArchiveDigest;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ArchiveNotice;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListArchivedArticles;

/**
 * Mounted under a slug that starts with another resource's, configures its own navigation item, and
 * registers two pages of its own beside its listing.
 */
final class ArticleArchiveResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $slug = 'articles-archive';

    protected static ?string $navigationLabel = 'Archive';

    protected static ?string $navigationGroup = 'Records';

    protected static string|BackedEnum|null $navigationIcon = 'heroicons:rectangle-stack';

    public function getNavigationBadge(): string
    {
        return '3';
    }

    public function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListArchivedArticles::route('/'),
            'digest' => ArchiveDigest::route('/digest'),
            'notice' => ArchiveNotice::route('/notice'),
        ];
    }
}
