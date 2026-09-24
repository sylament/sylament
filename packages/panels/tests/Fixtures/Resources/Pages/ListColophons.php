<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\CreateAction;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Panels\Tests\Fixtures\Resources\ColophonResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class ListColophons extends ListRecords
{
    protected static string $resource = ColophonResource::class;

    /**
     * Mounted by name, a modal create whose only key field is the identifier
     * association itself, picked through a relationship select.
     */
    public function createAction(): CreateAction
    {
        return CreateAction::make()->schema([
            Select::make('catalogue')->relationship('catalogue', 'title'),
            TextInput::make('note'),
        ]);
    }
}
