<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use Sylament\Actions\DissociateBulkAction;
use Sylament\Actions\Enums\ActionStatus;

/*
 * The chrome and the counting title, which need neither a table nor a database.
 * Both arms of the dissociate — the fetching one and the DQL `UPDATE` by key —
 * are pinned end to end by `AssociateDissociateActionsTest` in the **tables**
 * suite, where a real selection host and a real Doctrine connection exist.
 */

it('names itself dissociate', function (): void {
    expect(DissociateBulkAction::make()->getName())->toBe('dissociate')
        ->and(DissociateBulkAction::getDefaultName())->toBe('dissociate');
});

it('keeps an explicit name over the default', function (): void {
    expect(DissociateBulkAction::make('unlink')->getName())->toBe('unlink');
});

it('carries the bulk dissociate chrome', function (): void {
    $action = DissociateBulkAction::make();

    expect($action->getLabel())->toBe('Dissociate selected')
        ->and($action->getModalHeading())->toBe('Dissociate selected')
        ->and($action->getModalSubmitActionLabel())->toBe('Dissociate')
        ->and($action->getSuccessNotificationTitle())->toBe('Dissociated')
        ->and($action->getColor())->toBe('danger')
        ->and($action->getIcon())->toBe('heroicons:x-mark-20-solid')
        ->and($action->getModalIcon())->toBe('heroicons:x-mark')
        ->and($action->isConfirmationRequired())->toBeTrue();
});

it('inherits the bulk presets and deselects after completion', function (): void {
    $action = DissociateBulkAction::make();

    expect($action->isBulk())->toBeTrue()
        ->and($action->canAccessSelectedRecords())->toBeTrue()
        ->and($action->shouldFetchSelectedRecords())->toBeTrue()
        ->and($action->shouldDeselectRecordsAfterCompletion())->toBeTrue()
        ->and($action->getExtraAttributes())->toBe(['data-sy-table-bulk-only' => true]);
});

it('titles a partial failure with the two counts', function (): void {
    $action = DissociateBulkAction::make();
    $action->reportBulkProcessingSuccessfulRecordsCount(2);

    expect($action->getFailureNotificationTitle())->toBe('Dissociated 2 of 0')
        ->and($action->getStatus())->toBe(ActionStatus::Failure);
});

it('titles a complete failure with the total alone', function (): void {
    $action = DissociateBulkAction::make();
    $action->reportCompleteBulkProcessingFailure();

    expect($action->getFailureNotificationTitle())->toBe('Failed to dissociate 0 records');
});

it('interpolates the title-cased plural record label into the modal heading', function (): void {
    expect(DissociateBulkAction::make()->recordClass('App\\Entity\\BlogPost')->getModalHeading())->toBe('Dissociate selected Blog Posts')
        ->and(DissociateBulkAction::make()->getModalHeading())->toBe('Dissociate selected');
});
