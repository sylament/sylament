<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\PortfolioResource;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The clippings repeater and the seal group, each carrying a UI-only field no
 * record holds — the state a save's reload hands back as the record gives it.
 */
#[AsLiveComponent]
final class EditPortfolioStamped extends EditRecord
{
    protected static string $resource = PortfolioResource::class;

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)->components([
            TextInput::make('title'),
            Repeater::make('clippings')->relationship()->components([
                TextInput::make('text'),
                TextInput::make('scratch')->dehydrated(false),
            ]),
            Group::make([
                TextInput::make('title'),
                TextInput::make('serial'),
                TextInput::make('scratch')->dehydrated(false),
            ])->relationship('seal'),
        ]);
    }
}
