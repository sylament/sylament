<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\CreateAction;
use Sylament\Actions\DeleteAction;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\ImprintBindingResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class EditImprintBinding extends EditRecord
{
    protected static string $resource = ImprintBindingResource::class;

    /**
     * Never a header action: the resource registers a create page, so a header
     * `CreateAction` would render as a link to it. Mounted by name, it is the
     * modal arm — the one whose write has to be attached to the parent record
     * the page was opened under.
     */
    public function createAction(): CreateAction
    {
        return CreateAction::make();
    }

    public function deleteAction(): DeleteAction
    {
        return DeleteAction::make();
    }

    public function getHeaderActions(): array
    {
        return [$this->deleteAction()];
    }
}
