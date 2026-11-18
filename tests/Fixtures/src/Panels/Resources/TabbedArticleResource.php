<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Panels\Resources\Resource;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Filters\Filter;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListTabbedArticles;

/**
 * Articles under a slug with a slash, two to a page, searchable, sortable, filterable to drafts and groupable by
 * title; each row's hidden view action and its edit action carry URLs of their own.
 */
final class TabbedArticleResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'shelf/articles';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
            ])
            ->filters([
                Filter::make('drafts')->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("record.title LIKE 'Draft%'")),
            ])
            ->groups(['title'])
            ->paginated([2])
            ->recordActions([
                ViewAction::make()->url('/list-pages/hidden-view')->hidden(),
                EditAction::make()->url(static fn (Article $record): string => "/list-pages/edit-elsewhere/{$record->id}"),
                DeleteAction::make(),
            ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListTabbedArticles::route('/'),
        ];
    }
}
