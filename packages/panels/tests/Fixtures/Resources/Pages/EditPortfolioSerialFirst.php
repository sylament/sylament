<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\PortfolioResource;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * {@see EditPortfolioSealPaths} with the seal's serial mapped first, while the
 * related record is still an unloaded reference — the order Doctrine drops a
 * key write in.
 */
#[AsLiveComponent]
final class EditPortfolioSerialFirst extends EditRecord
{
    protected static string $resource = PortfolioResource::class;

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)->components([
            TextInput::make('title'),
            TextInput::make('seal.serial'),
            TextInput::make('seal.title'),
        ]);
    }
}
