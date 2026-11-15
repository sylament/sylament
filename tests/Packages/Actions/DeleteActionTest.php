<?php

declare(strict_types=1);

use Sylament\Actions\DeleteAction;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Actions\Entity\Product;
use Sylament\Tests\Fixtures\Actions\InvoiceLineTable;
use Sylament\Tests\Fixtures\Actions\OrderLineTable;
use Sylament\Tests\Fixtures\Actions\ProductTable;
use Sylament\Tests\Fixtures\Schemas\Entity\Invoice;
use Sylament\Tests\Fixtures\Schemas\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Schemas\Entity\OrderLine;

it("deletes the row's record and toasts", function (): void {
    $this->seed($kept = new Product('Kept'), $gone = new Product('Gone'));

    $this->createLiveComponent(ProductTable::class)
        ->assertCanSeeTableRecords([$kept, $gone])
        ->callAction(TestAction::make(DeleteAction::class)->table($gone))
        ->assertNotified('Deleted')
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
