<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Tests\Fixtures\Resources\RelicResource;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A nested create whose form leaves the `vault` key half out, so Doctrine
 * refuses the null half inside persist().
 */
#[AsLiveComponent]
final class CreateVaultlessRelic extends CreateRecord
{
    protected static string $resource = RelicResource::class;

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)->components([
            TextInput::make('niche'),
            TextInput::make('label'),
        ]);
    }
}
