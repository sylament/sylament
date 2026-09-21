<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateFlatStamp;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditFlatStamp;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListFlatStamps;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The same derived-identity records, top-level: a resource whose own form sets
 * the identifier association through a relationship `Select` onto a
 * single-identifier target, and the search root whose dotted attribute walks
 * half its own identifier.
 */
final class FlatStampResource extends Resource
{
    protected static ?string $recordClass = Stamp::class;

    protected static ?string $recordTitleAttribute = 'caption';

    protected static ?string $pluralRecordLabel = 'flat stamps';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('catalogue')->relationship('catalogue', 'title')->required(),
            TextInput::make('code')->required(),
            TextInput::make('caption'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('code'), TextColumn::make('caption')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    /**
     * @return array<string|array<string>>
     */
    public function getGloballySearchableAttributes(): array
    {
        return ['caption', 'catalogue.title'];
    }

    public function getPages(): array
    {
        return [
            'index' => ListFlatStamps::route('/'),
            'create' => CreateFlatStamp::route('/create'),
            'edit' => EditFlatStamp::route('/{record}/edit'),
        ];
    }
}
