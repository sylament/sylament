<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\BulkAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\DeleteBulkAction;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A live host with no table and no record class: a `delete` with no record to name, a bulk
 * `deleteSelected` and an `archiveSelected` that reads the selection.
 */
#[AsLiveComponent(name: 'Actions:TablelessBulkHost', template: '@Fixtures/actions/ToolbarHost.html.twig')]
class TablelessBulkHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    public function deleteAction(): DeleteAction
    {
        return DeleteAction::make();
    }

    public function deleteSelectedAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make('deleteSelected');
    }

    public function archiveSelectedAction(): BulkAction
    {
        return BulkAction::make('archiveSelected')->action(static function (BulkAction $action): void {
            $action->getSelectedRecords();
        });
    }
}
