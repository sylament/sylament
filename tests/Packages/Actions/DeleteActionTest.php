<?php

declare(strict_types=1);

use Sylament\Actions\DeleteAction;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Actions\Record\IdlessTicket;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Actions\TablelessBulkHost;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\DeletingProductTable;
use Sylament\Tests\Fixtures\Tables\InvoiceLineTable;
use Sylament\Tests\Fixtures\Tables\OrderLineTable;
use Sylament\Tests\Fixtures\Tables\ProductTable;

it("deletes the row's record and toasts", function (): void {
    $this->seed($kept = new Product('Kept'), $gone = new Product('Gone'));

    $this->createLiveComponent(ProductTable::class)
        ->assertCanSeeTableRecords([$kept, $gone])
        ->callAction(TestAction::make(DeleteAction::class)->table($gone))
        ->assertNotified('Deleted')
        ->assertNoRedirect()
        ->assertCanSeeTableRecords([$kept])
        ->assertCanNotSeeTableRecords([$gone]);
});

it('deletes a composite-keyed row, and only that row', function (string $table, object $line, object $sameHead, object $sameLine): void {
    $this->seed($line, $sameHead, $sameLine);

    $this->createLiveComponent($table)
        ->assertCanSeeTableRecords([$line, $sameHead, $sameLine])
        ->callAction(TestAction::make(DeleteAction::class)->table($line))
        ->assertCanSeeTableRecords([$sameHead, $sameLine])
        ->assertCanNotSeeTableRecords([$line]);
})->with([
    'scalar fields' => fn (): array => [OrderLineTable::class, new OrderLine(order: 7, line: 2), new OrderLine(order: 7, line: 3), new OrderLine(order: 8, line: 2)],
    'an association plus a scalar' => fn (): array => [InvoiceLineTable::class, new InvoiceLine($seven = new Invoice(7), line: 2), new InvoiceLine($seven, line: 3), new InvoiceLine(new Invoice(8), line: 2)],
]);

it('offers each row a danger "Delete" beside a trash icon, and deletes only once its confirmation is confirmed', function (): void {
    $this->seed($lamp = new Product('Lamp'));

    $tester = $this->createLiveComponent(ProductTable::class);

    expect($tester->render()->crawler())->toContainElement(SupportSelectors::LINK . SupportSelectors::color('danger'), 'trash-20-solidDelete');

    $tester->mountAction(TestAction::make('delete')->table($lamp))
        ->assertActionMounted(TestAction::make('delete')->table($lamp))
        ->assertCanSeeTableRecords([$lamp]);

    expect($tester->render()->crawler())->toContainButtons(Selectors::MODAL_FOOTER_ACTIONS, ['Cancel', 'Confirm']);

    $tester->callMountedAction()
        ->assertNotified('Deleted')
        ->assertCanNotSeeTableRecords([$lamp]);
});

it("heads its confirmation with the row's title, else the record label, else the bare verb", function (string $host, bool $onRow, string $heading): void {
    $this->seed($lamp = new Product('Lamp'));

    $tester = $this->createLiveComponent($host)->mountAction($onRow ? TestAction::make('delete')->table($lamp) : 'delete');

    expect($tester->render()->crawler())->toContainElement(Selectors::MODAL_HEADING, $heading);
})->with([
    'a table titling its rows' => [DeletingProductTable::class, true, 'Delete Lamp'],
    'a table that does not' => [ProductTable::class, true, 'Delete product'],
    'no record to name' => [TablelessBulkHost::class, false, 'Delete'],
]);

it('hands the record to the using() that replaces its delete, and toasts by its answer', function (string $action, string $toast): void {
    $this->seed($lamp = new Product('Lamp'));

    $this->createLiveComponent(DeletingProductTable::class)
        ->callAction(TestAction::make($action)->table($lamp))
        ->assertNotified($toast)
        ->assertCanSeeTableRecords([$lamp]);

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(["{$action} was handed Lamp"]);
})->with([
    'a false answer, the failure' => ['keep', 'Kept'],
    'a true answer, the success' => ['skip', 'Deleted'],
]);

it('refuses to delete from an instance the container did not build', function (): void {
    expect(fn (): mixed => new DeleteAction()->name('delete')->configure()->record(new Product('Lamp'))->call())
        ->toThrow(LogicException::class, 'DeleteAction needs the container-built instance — register it as a service so the manager registry is injected.');
});

it('refuses to delete a record no Doctrine manager handles', function (): void {
    expect(fn (): mixed => DeleteAction::make()->record(new IdlessTicket('T-1'))->call())
        ->toThrow(LogicException::class, 'No Doctrine manager handles ' . IdlessTicket::class . '.');
});
