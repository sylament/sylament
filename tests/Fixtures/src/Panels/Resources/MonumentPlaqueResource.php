<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\MonumentPlaque;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateMonumentPlaque;

/**
 * Plaques created under a {@see MonumentResource} record, built by a `makeRecord()` that hands their constructor a monument.
 */
final class MonumentPlaqueResource extends Resource
{
    protected static ?string $recordClass = MonumentPlaque::class;

    protected static ?string $parentResource = MonumentResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('locale'),
            TextInput::make('inscription'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'create' => CreateMonumentPlaque::route('/create'),
        ];
    }
}
