<?php

declare(strict_types=1);

use Sylament\Actions\DeleteAction;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Actions\Entity\Product;
use Sylament\Tests\Fixtures\Actions\OrderLineTable;
use Sylament\Tests\Fixtures\Actions\ProductTable;
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

it('deletes a composite-keyed row, and only that row', function (): void {
    $this->seed($line = new OrderLine(order: 7, line: 2), $other = new OrderLine(order: 7, line: 3));

    $this->createLiveComponent(OrderLineTable::class)
        ->assertCanSeeTableRecords([$line, $other])
        ->callAction(TestAction::make(DeleteAction::class)->table($line))
        ->assertCanSeeTableRecords([$other])
        ->assertCanNotSeeTableRecords([$line]);
});
