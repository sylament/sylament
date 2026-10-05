<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Locket;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateLocket;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListLockets;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * A single derived identity whose form maps the engraving alone, so the clasp
 * its constructor builds keeps the key `''`.
 */
final class LocketResource extends Resource
{
    protected static ?string $recordClass = Locket::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('engraving'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('engraving')]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListLockets::route('/'),
            'create' => CreateLocket::route('/create'),
        ];
    }
}
