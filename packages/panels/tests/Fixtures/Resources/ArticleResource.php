<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\Action;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Notifications\Notification;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateArticle;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditArticle;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListArticles;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

final class ArticleResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationGroup = 'Content';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
            TextInput::make('slug')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->sortable()->searchable(),
                TextColumn::make('status'),
            ])
            ->paginated([5, 10])
            ->recordActions([
                EditAction::make(),
                Action::make('delete')
                    ->requiresConfirmation()
                    ->action(function (Article $record): void {
                        $entityManager = $this->getRecordQuery()->getEntityManager();

                        $entityManager->remove($record);
                        $entityManager->flush();

                        Notification::make()
                            ->title(sprintf('Deleted "%s"', $record->getTitle()))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
