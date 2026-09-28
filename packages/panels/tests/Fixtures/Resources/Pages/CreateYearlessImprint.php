<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Tests\Fixtures\Resources\ImprintResource;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A create whose form leaves the `year` key half out, so the imprint reaches
 * the save with that half still the empty string the class starts it at.
 */
#[AsLiveComponent]
final class CreateYearlessImprint extends CreateRecord
{
    protected static string $resource = ImprintResource::class;

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)->components([
            TextInput::make('house')->required(),
            TextInput::make('label'),
        ]);
    }
}
