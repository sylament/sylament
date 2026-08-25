<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use Sylament\Actions\DeleteBulkAction;
use Sylament\Actions\Enums\ActionStatus;

/*
 * The chrome and the counting title, which need neither a table nor a database.
 * The delete itself is pinned end to end by `DeleteBulkActionTest` in the
 * **tables** suite, where a real selection host and a real Doctrine connection
 * exist.
 */

it('names itself delete', function (): void {
    expect(DeleteBulkAction::make()->getName())->toBe('delete')
        ->and(DeleteBulkAction::getDefaultName())->toBe('delete');
});

it('keeps an explicit name over the default', function (): void {
    expect(DeleteBulkAction::make('destroy')->getName())->toBe('destroy');
});

it('carries the bulk delete chrome', function (): void {
    $action = DeleteBulkAction::make();

    expect($action->getLabel())->toBe('Delete selected')
        ->and($action->getModalHeading())->toBe('Delete selected')
        ->and($action->getModalSubmitActionLabel())->toBe('Delete')
        ->and($action->getSuccessNotificationTitle())->toBe('Deleted')
        ->and($action->getColor())->toBe('danger')
        ->and($action->getIcon())->toBe('heroicons:trash-20-solid')
        ->and($action->isConfirmationRequired())->toBeTrue();
});

it('inherits the bulk presets and deselects after completion', function (): void {
    $action = DeleteBulkAction::make();

    expect($action->isBulk())->toBeTrue()
        ->and($action->canAccessSelectedRecords())->toBeTrue()
        ->and($action->shouldFetchSelectedRecords())->toBeTrue()
        ->and($action->shouldDeselectRecordsAfterCompletion())->toBeTrue()
        ->and($action->getExtraAttributes())->toBe(['data-sy-table-bulk-only' => true]);
});

it('titles a partial failure with the two counts', function (): void {
    $action = DeleteBulkAction::make();
    $action->reportBulkProcessingSuccessfulRecordsCount(2);

    expect($action->getFailureNotificationTitle())->toBe('Deleted 2 of 0')
        ->and($action->getStatus())->toBe(ActionStatus::Failure);
});

it('titles a complete failure with the total alone', function (): void {
    $action = DeleteBulkAction::make();
    $action->reportCompleteBulkProcessingFailure();

    expect($action->getFailureNotificationTitle())->toBe('Failed to delete 0 records');
});

it('interpolates the title-cased plural record label into the modal heading', function (): void {
    expect(DeleteBulkAction::make()->recordClass('App\\Entity\\BlogPost')->getModalHeading())->toBe('Delete selected Blog Posts')
        ->and(DeleteBulkAction::make()->getModalHeading())->toBe('Delete selected');
});
