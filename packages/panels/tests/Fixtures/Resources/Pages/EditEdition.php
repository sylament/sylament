<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\EditionResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class EditEdition extends EditRecord
{
    protected static string $resource = EditionResource::class;

    /**
     * The ordinary record-bound header action, on a record one of whose
     * identifier fields is named `id` — so what the trigger mints for the
     * mounted frame is half the key.
     */
    public function editAction(): EditAction
    {
        return EditAction::make()->record($this->getRecord());
    }

    public function deleteAction(): DeleteAction
    {
        return DeleteAction::make();
    }

    public function getHeaderActions(): array
    {
        return [
            $this->editAction(),
            $this->deleteAction(),
        ];
    }
}
