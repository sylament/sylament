<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\CreateAction;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\VerseResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class EditVerse extends EditRecord
{
    protected static string $resource = VerseResource::class;

    /**
     * Record-bound, so a mounted frame naming another record's key reaches the
     * page's own resolution — which on a nested page is the parent-scoped one.
     */
    public function editAction(): EditAction
    {
        return EditAction::make()->record($this->getRecord());
    }

    /**
     * The delete whose success redirect a nested page answers with its parent's
     * page rather than an index of its own.
     */
    public function deleteAction(): DeleteAction
    {
        return DeleteAction::make();
    }

    /**
     * Never a header action: the resource registers a create page, so a header
     * `CreateAction` would render as a link to it. Mounted by name, it is the
     * modal arm — the one that has to attach what it creates to the parent.
     */
    public function createAction(): CreateAction
    {
        return CreateAction::make();
    }

    public function getHeaderActions(): array
    {
        return [$this->deleteAction()];
    }
}
