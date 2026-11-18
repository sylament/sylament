<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\QuayBollard;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateCaselessBollard;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditCaselessBollard;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListCaselessBollards;

/**
 * Bollards keyed by a string their column stores lower-cased, with an edit page a create redirects to.
 */
final class CaselessBollardResource extends Resource
{
    protected static ?string $recordClass = QuayBollard::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code'),
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
            'index' => ListCaselessBollards::route('/'),
            'create' => CreateCaselessBollard::route('/create'),
            'edit' => EditCaselessBollard::route('/{record}/edit'),
        ];
    }
}
