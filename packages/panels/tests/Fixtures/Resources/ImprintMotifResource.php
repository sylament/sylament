<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Motif;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateImprintMotif;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditImprintMotif;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The index-less nested shape over the many-to-many arm: its records are
 * reached only through one imprint, so the parent scope is the join table read
 * from the composite side rather than a foreign key on the record.
 */
final class ImprintMotifResource extends Resource
{
    protected static ?string $recordClass = Motif::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $parentResource = BinderyResource::class;

    protected static bool $isGloballySearchable = false;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('name')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public function getPages(): array
    {
        return [
            'create' => CreateImprintMotif::route('/create'),
            'edit' => EditImprintMotif::route('/{record}/edit'),
        ];
    }
}
