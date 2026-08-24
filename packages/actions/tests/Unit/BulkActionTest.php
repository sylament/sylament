<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use Doctrine\ORM\QueryBuilder;
use LogicException;
use RuntimeException;
use Sylament\Actions\Action;
use Sylament\Actions\BulkAction;
use Sylament\Actions\Enums\ActionStatus;
use Sylament\Actions\Tests\Fixtures\Concerns\BothContractsHostFixture;
use Sylament\Tables\Contracts\InteractsWithTable;

it('presets the two bulk flags', function (): void {
    $action = BulkAction::make('delete');

    expect($action->isBulk())->toBeTrue()
        ->and($action->canAccessSelectedRecords())->toBeTrue()
        ->and($action->hasCanAccessSelectedRecords())->toBeTrue();
});

it('lets a caller turn either preset flag back off', function (): void {
    $action = BulkAction::make('delete')->bulk(false)->accessSelectedRecords(false);

    expect($action->isBulk())->toBeFalse()
        ->and($action->canAccessSelectedRecords())->toBeFalse()
        ->and($action->hasCanAccessSelectedRecords())->toBeFalse();
});

it('stamps the bulk-only marker on its trigger attributes', function (): void {
    expect(BulkAction::make('delete')->getExtraAttributes())
        ->toBe(['data-sy-table-bulk-only' => true]);
});

it('lets a caller attribute win over the bulk-only marker', function (): void {
    expect(BulkAction::make('delete')->extraAttributes(['data-sy-table-bulk-only' => 'no'])->getExtraAttributes())
        ->toBe(['data-sy-table-bulk-only' => 'no']);
});

it('starts both selected-record counters at zero', function (): void {
    $action = BulkAction::make('delete');

    expect($action->getTotalSelectedRecordsCount())->toBe(0)
        ->and($action->failureNotificationTitle(fn (int $successCount): string => "s{$successCount}")->getFailureNotificationTitle())
        ->toBe('s0');
});

it('reports Success while the successful count still matches the total', function (): void {
    expect(BulkAction::make('delete')->getStatus())->toBe(ActionStatus::Success);
});

it('reports Failure once a single processing failure moves the successful count off the total', function (): void {
    $action = BulkAction::make('delete');
    $action->reportBulkProcessingFailure();

    expect($action->getStatus())->toBe(ActionStatus::Failure);
});

it('moves the successful count to the reported count', function (): void {
    $action = BulkAction::make('delete')
        ->failureNotificationTitle(fn (int $successCount, int $failureCount, int $totalCount, bool $isAll): string => "{$successCount}/{$failureCount}/{$totalCount}/" . ($isAll ? 'all' : 'some'));
    $action->reportBulkProcessingSuccessfulRecordsCount(3);

    expect($action->getFailureNotificationTitle())->toBe('3/-3/0/some')
        ->and($action->getStatus())->toBe(ActionStatus::Failure);
});

it('zeroes the successful count on a complete processing failure', function (): void {
    $action = BulkAction::make('delete')
        ->failureNotificationTitle(fn (int $successCount, bool $isAll): string => "{$successCount}/" . ($isAll ? 'all' : 'some'));
    $action->reportBulkProcessingSuccessfulRecordsCount(3);
    $action->reportCompleteBulkProcessingFailure();

    expect($action->getFailureNotificationTitle())->toBe('0/all');
});

it('keeps an explicit status over the selected-records tally', function (): void {
    $action = BulkAction::make('delete');
    $action->reportBulkProcessingFailure();
    $action->success();

    expect($action->getStatus())->toBe(ActionStatus::Success);
});

it('reports Success for a plain action whose counters disagree, since it accesses no selected records', function (): void {
    $action = Action::make('delete');
    $action->reportBulkProcessingFailure();

    expect($action->canAccessSelectedRecords())->toBeFalse()
        ->and($action->getStatus())->toBe(ActionStatus::Success);
});

it('fetches selected records by default and lets a caller opt out', function (): void {
    expect(BulkAction::make('delete')->shouldFetchSelectedRecords())->toBeTrue()
        ->and(BulkAction::make('delete')->fetchSelectedRecords(false)->shouldFetchSelectedRecords())->toBeFalse();
});

it('deselects nothing after completion by default and lets a caller opt in', function (): void {
    expect(BulkAction::make('delete')->shouldDeselectRecordsAfterCompletion())->toBeFalse()
        ->and(BulkAction::make('delete')->deselectRecordsAfterCompletion()->shouldDeselectRecordsAfterCompletion())->toBeTrue();
});

/*
 * The two record readers share one gate helper, so both the access flag and the
 * host-shape check are asserted through each of them.
 */
it('refuses the selected records to an action that does not access them', function (): void {
    expect(fn (): array => Action::make('archive')->getSelectedRecords())
        ->toThrow(LogicException::class, 'The action [archive] is attempting to access the selected records from the table, but it is not using [accessSelectedRecords()], so they are not available.');
});

it('refuses the selected-records query to an action that does not access them', function (): void {
    expect(fn (): QueryBuilder => Action::make('archive')->getSelectedRecordsQuery())
        ->toThrow(LogicException::class, 'The action [archive] is attempting to access the selected records from the table');
});

it('refuses the selected records when the live component is no table host', function (): void {
    $action = BulkAction::make('archive')->liveComponent(new BothContractsHostFixture());

    expect(fn (): array => $action->getSelectedRecords())
        ->toThrow(LogicException::class, InteractsWithTable::class);
});

it('refuses the selected records when the action has no live component at all', function (): void {
    expect(fn (): array => BulkAction::make('archive')->getSelectedRecords())
        ->toThrow(RuntimeException::class, 'Action is not part of LiveComponent');
});
