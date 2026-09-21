<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\CreateAction;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\ImprintResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class EditImprint extends EditRecord
{
    protected static string $resource = ImprintResource::class;

    /**
     * Record-bound on a composite record with no field named `id`: nothing is
     * minted for the mounted frame and the declared record stands.
     */
    public function editAction(): EditAction
    {
        return EditAction::make()->record($this->getRecord());
    }

    public function deleteAction(): DeleteAction
    {
        return DeleteAction::make();
    }

    /**
     * Never a header action: the resource registers a create page, so a header
     * `CreateAction` would render as a link to it. Mounted by name, it is the
     * modal arm.
     */
    public function createAction(): CreateAction
    {
        return CreateAction::make();
    }

    public function getHeaderActions(): array
    {
        return [
            $this->editAction(),
            $this->deleteAction(),
        ];
    }
}
