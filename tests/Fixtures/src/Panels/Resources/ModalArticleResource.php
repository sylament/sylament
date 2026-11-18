<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListModalArticles;

/**
 * Registers only its index, so its record actions open modals; its form declares no columns, and its view action
 * is hidden on a `Sealed` article. Two to a page.
 */
final class ModalArticleResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
            ])
            ->paginated([2])
            ->recordActions([
                ViewAction::make()->hidden(static fn (Article $record): bool => $record->title === 'Sealed'),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListModalArticles::route('/'),
        ];
    }
}
