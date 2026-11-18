<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\DeleteAction;
use Sylament\Panels\Resources\Resource;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListRestampedArticles;

/**
 * Titles an article in quotes, and its table replaces the query it is stamped with by one leaving out the
 * `Withdrawn` articles and links each row to a URL of its own.
 */
final class RestampedArticleResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $recordTitleAttribute = 'title';

    public function getRecordTitle(?object $record): string
    {
        return '"' . parent::getRecordTitle($record) . '"';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): QueryBuilder => $this->getRecordQuery()->andWhere("record.title NOT LIKE 'Withdrawn%'"))
            ->columns([
                TextColumn::make('title'),
            ])
            ->recordUrl(static fn (Article $record): string => "/list-pages/clippings/{$record->id}")
            ->recordActions([
                DeleteAction::make(),
            ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListRestampedArticles::route('/'),
        ];
    }
}
