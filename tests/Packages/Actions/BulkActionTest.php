<?php

declare(strict_types=1);

use Sylament\Actions\BulkAction;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\SelectionReadingProductTable;

it("runs from the table's toolbar over the selected records, as a plain action made a bulk one that reads the selection does", function (string $action): void {
    $this->seed($lamp = new Product('Lamp'), $vase = new Product('Vase'), new Product('Rug'));

    $this->createLiveComponent(SelectionReadingProductTable::class)
        ->selectTableRecords([$lamp, $vase])
        ->callAction(TestAction::make($action)->table()->bulk());

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(["{$action} read Lamp, Vase"]);
})->with([
    'a bulk action' => ['archive'],
    'bulk() and accessSelectedRecords()' => ['peek'],
]);

it('hides its trigger while no row is selected and shows it once one is', function (): void {
    $this->seed($lamp = new Product('Lamp'));
    $archive = '[data-live-name-param="archive"]';

    $tester = $this->createLiveComponent(SelectionReadingProductTable::class);

    expect($tester->render()->crawler())->toContainElement(Selectors::BULK_ONLY . "[hidden] {$archive}", 'Archive')
        ->and($tester->selectTableRecords([$lamp])->render()->crawler())->toContainElement(Selectors::BULK_ONLY . ":not([hidden]) {$archive}", 'Archive');
});

it('marks its trigger bulk-only unless its own attributes say otherwise', function (): void {
    $unmarked = $this->renderViewComponent(BulkAction::make('archive')->extraAttributes(['data-sy-table-bulk-only' => false]));

    expect($this->renderViewComponent(BulkAction::make('archive')))->toContainElement('button' . Selectors::BULK_ONLY, 'Archive')
        ->and($unmarked)->toContainElement('button', 'Archive')
        ->and($unmarked->filter('button' . Selectors::BULK_ONLY))->toHaveCount(0);
});
