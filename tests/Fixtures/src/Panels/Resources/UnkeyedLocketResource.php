<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\ClaspedLocket;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateUnkeyedLocket;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListUnkeyedLockets;

/**
 * Lockets whose whole key is a clasp code no field maps, so it flattens to `''` unless the create page sets it.
 */
final class UnkeyedLocketResource extends Resource
{
    protected static ?string $recordClass = ClaspedLocket::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
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
            'index' => ListUnkeyedLockets::route('/'),
            'create' => CreateUnkeyedLocket::route('/create'),
        ];
    }
}
