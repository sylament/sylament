<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\PortfolioResource;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The seal written through dotted state paths rather than a relationship
 * group, its title before its serial — so the related record is loaded by the
 * first write before the second one reaches its key.
 */
#[AsLiveComponent]
final class EditPortfolioSealPaths extends EditRecord
{
    protected static string $resource = PortfolioResource::class;

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)->components([
            TextInput::make('title'),
            TextInput::make('seal.title'),
            TextInput::make('seal.serial'),
        ]);
    }
}
