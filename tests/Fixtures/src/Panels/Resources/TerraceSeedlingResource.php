<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditTerraceSeedling;

/**
 * A terrace's seedlings as a resource nested under the terrace's, a parent keyed by two scalars, the terrace's estate and number:
 * a name field and an edit page.
 */
final class TerraceSeedlingResource extends Resource
{
    protected static ?string $recordClass = Seedling::class;

    protected static ?string $parentResource = TerraceResource::class;

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'edit' => EditTerraceSeedling::route('/{record}/edit'),
        ];
    }
}
