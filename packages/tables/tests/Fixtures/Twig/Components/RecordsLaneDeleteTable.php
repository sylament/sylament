<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DeleteBulkAction;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Actions\RecordingDeleteBulkAction;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A by-key `DeleteBulkAction` on a lane that cannot answer one: the records()
 * table has no entity manager and no record class, so the action's own refusal
 * is the only thing the call can produce. It is the rig for the placement —
 * a refusal raised outside the delete's `catch (Throwable)` reaches the caller,
 * one raised inside it becomes a "Failed to delete" toast no test can see.
 * The action is the recording subclass, delegating, so a test can also see
 * that neither refusal reaches the override point.
 */
#[AsLiveComponent(name: 'RecordsLaneDeleteTable', template: '@TablesFixtures/components/RecordsLaneDeleteTable.html.twig')]
class RecordsLaneDeleteTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => [
                ['__key' => '1', 'label' => 'One'],
                ['__key' => '2', 'label' => 'Two'],
            ])
            ->columns([TextColumn::make('label')])
            ->toolbarActions([RecordingDeleteBulkAction::make()->fetchSelectedRecords(false)]);
    }

    /**
     * The same action as a **host** action rather than a table one, so mounting
     * it without the table flag resolves it through here and leaves it with no
     * table at all — the delete's other placement refusal.
     */
    public function untabledDeleteAction(): DeleteBulkAction
    {
        return RecordingDeleteBulkAction::make('untabledDelete')->fetchSelectedRecords(false);
    }
}
