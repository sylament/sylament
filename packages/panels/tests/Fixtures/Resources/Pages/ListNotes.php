<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Panels\Tests\Fixtures\Resources\NoteResource;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent]
final class ListNotes extends ListRecords
{
    protected static string $resource = NoteResource::class;

    #[LiveProp(writable: true)]
    public bool $showTabs = false;

    /**
     * Hides the first of the two actions the row-click seeding walks, so a case
     * can tell the seeding skips a hidden one rather than making the row mount
     * an action it never shows.
     */
    #[LiveProp(writable: true)]
    public bool $hidesViewAction = false;

    public function table(Table $table): Table
    {
        $table = parent::table($table);

        if (!$this->hidesViewAction) {
            return $table;
        }

        return $table->recordActions([
            ViewAction::make()->hidden(),
            EditAction::make(),
        ]);
    }

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
