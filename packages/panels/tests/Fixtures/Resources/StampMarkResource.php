<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\StampMark;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateStampMark;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditStampMark;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListStampMarks;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * A resource nested under a derived-identity parent: it lists, binds, creates,
 * edits and deletes through a parent record keyed by an association plus a
 * converting scalar, which is what the scope it resolves each record through
 * now answers for.
 */
final class StampMarkResource extends Resource
{
    protected static ?string $recordClass = StampMark::class;

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?string $parentResource = StampResource::class;

    protected static bool $isGloballySearchable = false;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('label')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListStampMarks::route('/'),
            'create' => CreateStampMark::route('/create'),
            'edit' => EditStampMark::route('/{record}/edit'),
        ];
    }
}
