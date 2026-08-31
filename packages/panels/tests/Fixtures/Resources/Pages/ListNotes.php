<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\CreateAction;
use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Panels\Tests\Fixtures\Resources\NoteResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class ListNotes extends ListRecords
{
    protected static string $resource = NoteResource::class;

    /**
     * Declared as `<name>Action()` for the reason
     * {@see ListArticles::purgeAction()} records: a mount request rebuilds the
     * page before any render has cached the header actions, so the frame
     * resolves through actions' method discovery or not at all.
     */
    public function createAction(): CreateAction
    {
        return CreateAction::make();
    }

    public function getHeaderActions(): array
    {
        return [
            $this->createAction(),
        ];
    }
}
