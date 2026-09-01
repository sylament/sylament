<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Line;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateLine;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditLine;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListLines;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ParentRecordProbePage;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The depth-two arm of the rig: a nested resource whose own parent is nested,
 * registering an index page of its own — and deliberately untitled, so a
 * caller reading a record title falls back to the record label.
 */
final class LineResource extends Resource
{
    protected static ?string $recordClass = Line::class;

    protected static ?string $parentResource = VerseResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('content')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('content')]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListLines::route('/'),
            'create' => CreateLine::route('/create'),
            'edit' => EditLine::route('/{record}/edit'),
            'parent-probe' => new PageRegistration(ParentRecordProbePage::class, '/{record}/parent-probe'),
        ];
    }
}
