<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use Sylament\Actions\DetachBulkAction;
use Sylament\Actions\Enums\ActionStatus;

/*
 * The chrome, the bulk presets and the counting title, which need neither a
 * table nor a database. The detach itself — both arms — is pinned end to end by
 * `AttachDetachActionsTest` in the **tables** suite.
 */

it('names itself detach', function (): void {
    expect(DetachBulkAction::make()->getName())->toBe('detach')
        ->and(DetachBulkAction::getDefaultName())->toBe('detach');
});

it('keeps an explicit name over the default', function (): void {
    expect(DetachBulkAction::make('detachTopics')->getName())->toBe('detachTopics');
});

it('carries the bulk detach chrome', function (): void {
    $action = DetachBulkAction::make();

    expect($action->getLabel())->toBe('Detach selected')
        ->and($action->getModalHeading())->toBe('Detach selected')
        ->and(DetachBulkAction::make()->recordClass('App\\Entity\\BlogPost')->getModalHeading())->toBe('Detach selected Blog Posts')
        ->and($action->getModalSubmitActionLabel())->toBe('Detach')
        ->and($action->getSuccessNotificationTitle())->toBe('Detached')
        ->and($action->getColor())->toBe('danger')
        ->and($action->getIcon())->toBe('heroicons:x-mark-20-solid')
        ->and($action->getModalIcon())->toBe('heroicons:x-mark')
        ->and($action->isConfirmationRequired())->toBeTrue();
});

it('inherits the bulk presets and deselects after completion', function (): void {
    $action = DetachBulkAction::make();

    expect($action->isBulk())->toBeTrue()
        ->and($action->canAccessSelectedRecords())->toBeTrue()
        ->and($action->shouldFetchSelectedRecords())->toBeTrue()
        ->and($action->shouldDeselectRecordsAfterCompletion())->toBeTrue()
        ->and($action->getExtraAttributes())->toBe(['data-sy-table-bulk-only' => true]);
});

it('titles a partial failure with the two counts', function (): void {
    $action = DetachBulkAction::make();
    $action->reportBulkProcessingSuccessfulRecordsCount(2);

    expect($action->getFailureNotificationTitle())->toBe('Detached 2 of 0')
        ->and($action->getStatus())->toBe(ActionStatus::Failure);
});

it('titles a complete failure with the total alone', function (): void {
    $action = DetachBulkAction::make();
    $action->reportCompleteBulkProcessingFailure();

    expect($action->getFailureNotificationTitle())->toBe('Failed to detach 0 records');
});
