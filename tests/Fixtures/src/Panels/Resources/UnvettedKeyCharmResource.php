<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\SatchelCharm;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateUnvettedKeyCharm;

/**
 * Charms created under a {@see ManyToManySatchelResource} record, a many-to-many parent, keyed by two scalars the form maps
 * with the key rules switched off, so a blank half reaches the write.
 */
final class UnvettedKeyCharmResource extends Resource
{
    protected static ?string $recordClass = SatchelCharm::class;

    protected static ?string $parentResource = ManyToManySatchelResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->validateRecordKey(false)
            ->components([
                TextInput::make('tier')->integer(),
                TextInput::make('code')->required(false),
                TextInput::make('label'),
            ]);
    }

    public function getPages(): array
    {
        return [
            'create' => CreateUnvettedKeyCharm::route('/create'),
        ];
    }
}
