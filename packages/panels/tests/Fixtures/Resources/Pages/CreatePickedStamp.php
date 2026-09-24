<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Tests\Fixtures\Resources\StampResource;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A nested create whose form also maps the association the parent link
 * fills — the misconfiguration where the form picks one catalogue while the
 * page is opened under another.
 */
#[AsLiveComponent]
final class CreatePickedStamp extends CreateRecord
{
    protected static string $resource = StampResource::class;

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)->components([
            Select::make('catalogue')->relationship('catalogue', 'title'),
            TextInput::make('code')->required(),
            TextInput::make('caption'),
        ]);
    }
}
