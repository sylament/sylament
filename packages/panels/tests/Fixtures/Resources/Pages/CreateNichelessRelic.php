<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Tests\Fixtures\Resources\RelicResource;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A nested create whose form leaves the `niche` key half out, so the relic
 * reaches persist() with that half still the empty string and the page's
 * blank-key read refuses it.
 */
#[AsLiveComponent]
final class CreateNichelessRelic extends CreateRecord
{
    protected static string $resource = RelicResource::class;

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)->components([
            TextInput::make('vault'),
            TextInput::make('label'),
        ]);
    }
}
