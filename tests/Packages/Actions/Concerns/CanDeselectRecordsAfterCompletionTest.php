<?php

declare(strict_types=1);

use Sylament\Actions\BulkAction;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Actions\MountedActionsHost;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Tables\SelectionReadingProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('clears the selection once it ran when told to, even after halting, and keeps it otherwise', function (string $action, int $selected): void {
    $this->seed($lamp = new Product('Lamp'), $vase = new Product('Vase'), new Product('Rug'));

    $tester = $this->createLiveComponent(SelectionReadingProductTable::class)->selectTableRecords([$lamp, $vase]);

    expect($tester->render()->crawler()->filter(Selectors::SELECTED_RECORD))->toHaveCount(2)
        ->and($tester->callAction(TestAction::make($action)->table()->bulk())->render()->crawler()->filter(Selectors::SELECTED_RECORD))->toHaveCount($selected);
})->with([
    'told to' => ['tidy', 0],
    'told to, halting' => ['tidyHalted', 0],
    'not told to' => ['archive', 2],
]);

it('runs when told to clear a selection it has no table to clear', function (Closure $bind): void {
    expect($bind(BulkAction::make('tidy')->deselectRecordsAfterCompletion()->action(static fn (): string => 'tidied'))->call())->toBe('tidied');
})->with([
    'no host' => [static fn (BulkAction $action): BulkAction => $action],
    'a host that is no table' => [static fn (BulkAction $action): BulkAction => $action->liveComponent(new MountedActionsHost())],
]);
