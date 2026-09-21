<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\StampMark;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateStampMark;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditStampMark;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * A resource nested under a composite parent: it mounts and it routes, and
 * every page of it refuses at the scope it resolves its record through, which
 * is the boundary the epic stops at.
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
        return $table->columns([TextColumn::make('label')]);
    }

    public function getPages(): array
    {
        return [
            'create' => CreateStampMark::route('/create'),
            'edit' => EditStampMark::route('/{record}/edit'),
        ];
    }
}
