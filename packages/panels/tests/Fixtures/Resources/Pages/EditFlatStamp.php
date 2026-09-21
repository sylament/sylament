<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\FlatStampResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class EditFlatStamp extends EditRecord
{
    protected static string $resource = FlatStampResource::class;

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
