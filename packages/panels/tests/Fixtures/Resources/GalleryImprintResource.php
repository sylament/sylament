<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Imprint;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateGalleryImprint;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditGalleryImprint;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The composite record nested under a many-to-many parent: the index-less
 * shape again, so its records are reached only through one gallery, which is
 * what puts the parent scope on a many-to-many rather than a one-to-many.
 * {@see ImprintResource} is the searchable resource over these records, so
 * this one opts out.
 */
final class GalleryImprintResource extends Resource
{
    protected static ?string $recordClass = Imprint::class;

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?string $parentResource = GalleryResource::class;

    protected static bool $isGloballySearchable = false;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('house')->required(),
            TextInput::make('year')->required(),
            TextInput::make('label'),
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
            'create' => CreateGalleryImprint::route('/create'),
            'edit' => EditGalleryImprint::route('/{record}/edit'),
        ];
    }
}
