<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditGardenPlant;

/**
 * A garden's plants, the many-to-many the garden owns, as a resource nested under the garden's,
 * naming its parent through the getter rather than the static: a name field and an edit page.
 */
final class GardenPlantResource extends Resource
{
    protected static ?string $recordClass = Plant::class;

    /**
     * @return class-string<GardenResource>
     */
    public function getParentResource(): string
    {
        return GardenResource::class;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
        ]);
    }

    public function getPages(): array
    {
        return [
            'edit' => EditGardenPlant::route('/{record}/edit'),
        ];
    }
}
