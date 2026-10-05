<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Countermark;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateCountermark;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * A derived identity nested under {@see MillResource} with a create page alone,
 * whose form maps the initials the constructor also takes.
 */
final class CountermarkResource extends Resource
{
    protected static ?string $recordClass = Countermark::class;

    protected static ?string $parentResource = MillResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('initials')->required(),
            TextInput::make('caption'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('initials'), TextColumn::make('caption')]);
    }

    public function getPages(): array
    {
        return [
            'create' => CreateCountermark::route('/create'),
        ];
    }
}
