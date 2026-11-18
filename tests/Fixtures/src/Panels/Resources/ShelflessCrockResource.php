<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\LarderCrock;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateShelflessCrock;

/**
 * Crocks created under a {@see CrockCellarResource} record, keyed by that larder and a shelf no field maps, so the shelf half stays blank.
 */
final class ShelflessCrockResource extends Resource
{
    protected static ?string $recordClass = LarderCrock::class;

    protected static ?string $parentResource = CrockCellarResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'create' => CreateShelflessCrock::route('/create'),
        ];
    }
}
