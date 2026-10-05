<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Chapter;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditHushChapter;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListHushChapters;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The root of the `hush` panel's nesting, and the parent {@see HushVerseResource}
 * hangs off.
 */
final class HushChapterResource extends Resource
{
    protected static ?string $recordClass = Chapter::class;

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('title')]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListHushChapters::route('/'),
            'edit' => EditHushChapter::route('/{record}/edit'),
        ];
    }
}
