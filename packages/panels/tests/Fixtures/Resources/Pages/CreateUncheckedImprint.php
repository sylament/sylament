<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Tests\Fixtures\Resources\ImprintResource;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * An imprint create whose form maps both key halves, requires neither, and
 * switches the key rules off, so a blank half reaches persist().
 */
#[AsLiveComponent]
final class CreateUncheckedImprint extends CreateRecord
{
    protected static string $resource = ImprintResource::class;

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)
            ->components([
                TextInput::make('house')->required(false),
                TextInput::make('year')->required(false),
                TextInput::make('label'),
            ])
            ->validateRecordKey(false);
    }
}
