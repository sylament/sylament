<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Panels\Resources\ShelflessCrockResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A crock create page whose form maps the shelf beside the label, so the larder half of the key is the parent's alone.
 */
#[AsLiveComponent]
final class CreateShelvedCrock extends CreateRecord
{
    protected static string $resource = ShelflessCrockResource::class;

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)->components([
            TextInput::make('shelf'),
            TextInput::make('label'),
        ]);
    }
}
