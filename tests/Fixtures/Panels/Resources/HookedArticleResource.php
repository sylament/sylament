<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Panels\Entity\HookedArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateHookedArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditHookedArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListHookedArticles;

final class HookedArticleResource extends Resource
{
    protected static ?string $recordClass = HookedArticle::class;

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
        ]);
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
            'index' => ListHookedArticles::route('/'),
            'create' => CreateHookedArticle::route('/create'),
            'edit' => EditHookedArticle::route('/{record}/edit'),
        ];
    }
}
