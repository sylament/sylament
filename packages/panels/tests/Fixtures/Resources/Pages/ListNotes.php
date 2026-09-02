<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\CreateAction;
use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Panels\Tests\Fixtures\Resources\NoteResource;
use Sylament\Schemas\Components\Tabs\Tab;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent]
final class ListNotes extends ListRecords
{
    protected static string $resource = NoteResource::class;

    #[LiveProp(writable: true)]
    public bool $showTabs = false;

    /**
     * Tabs this listing declares only once it is told to, so the strip's first
     * render is a hydration with no mount hook in front of it — the one path
     * the render seam answers for on its own.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        if (!$this->showTabs) {
            return [];
        }

        return [
            'all' => Tab::make(),
            'titled' => Tab::make(),
        ];
    }

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
