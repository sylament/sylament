<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\StampedBulletin;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateListenerStampedBulletin;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListListenerStampedBulletins;

/**
 * Bulletins whose key half `stamp` no field maps, so it is blank until the entity's prePersist callback stamps it.
 */
final class ListenerStampedBulletinResource extends Resource
{
    protected static ?string $recordClass = StampedBulletin::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('scope'),
            TextInput::make('label'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('label'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListListenerStampedBulletins::route('/'),
            'create' => CreateListenerStampedBulletin::route('/create'),
        ];
    }
}
