<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Chapter;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateChapter;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditChapter;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListChapters;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The root of the nested-resource rig: the only resource of the `nest` panel
 * that registers navigation, and the parent {@see VerseResource} hangs off.
 */
final class ChapterResource extends Resource
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
            'index' => ListChapters::route('/'),
            'create' => CreateChapter::route('/create'),
            'edit' => EditChapter::route('/{record}/edit'),
        ];
    }
}
