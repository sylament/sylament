<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\CreateAction;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Tests\Fixtures\Panels\Resources\VariantHerbResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * An edit page whose header carries a create, an edit, a view and a delete action, each with the page's defaults, and
 * whose form row follows Save with a `rename` and a `sow` (a create) modal, each asking for a name no other herb carries.
 * The row leaves out the Cancel link: its URL is minted as the row is built, and the tester, reading the page outside any
 * request with an action mounted, would mint it for the default panel, which lacks this resource.
 */
#[AsLiveComponent]
final class EditActionedHerb extends EditRecord
{
    protected static string $resource = VariantHerbResource::class;

    public function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            EditAction::make(),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            Action::make('rename')->schema([TextInput::make('name')->unique()]),
            CreateAction::make('sow')->schema([TextInput::make('name')->unique()]),
        ];
    }
}
