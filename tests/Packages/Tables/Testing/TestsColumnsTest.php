<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Sylament\Tables\Columns\Column;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\BrowsableProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('sorts the table by a column in the direction given', function (): void {
    $this->seed($alpha = new Product('Alpha'), $beta = new Product('Beta'));

    $this->createLiveComponent(BrowsableProductTable::class)
        ->sortTable('name', 'desc')
        ->assertCanSeeTableRecords([$beta, $alpha], inOrder: true);
});

it('searches the table', function (): void {
    $this->seed(new Product('Lamp'), $desk = new Product('Desk'));

    $this->createLiveComponent(BrowsableProductTable::class)
        ->searchTable('Lam')
        ->assertCanNotSeeTableRecords([$desk]);
});

it('searches a column on its own', function (): void {
    $this->seed(new Product('Lamp'), $desk = new Product('Desk'));

    $this->createLiveComponent(BrowsableProductTable::class)
        ->searchTableColumns(['name' => 'Lam'])
        ->assertCanNotSeeTableRecords([$desk]);
});

it('shows every toggleable column', function (): void {
    $this->seed(new Product('Lamp'));

    $crawler = $this->createLiveComponent(BrowsableProductTable::class)
        ->toggleAllTableColumns()
        ->render()
        ->crawler();

    expect($crawler->filter(Selectors::headerCell('id')))->toHaveCount(1);
});

it("calls a column's action on a record given as an object or as its key", function (): void {
    $this->seed($lamp = new Product('Lamp'), $desk = new Product('Desk'));

    $tester = $this->createLiveComponent(BrowsableProductTable::class)->callTableColumnAction('name', $lamp);

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['open Lamp']);

    $tester->callTableColumnAction('name', (string) $desk->id);

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['open Desk']);
});

it('fails asserting that a table column exists when the table has none of that name', function (): void {
    expect(fn () => $this->createLiveComponent(BrowsableProductTable::class)->assertTableColumnExists('price'))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that a table column with name [price] exists on the [' . BrowsableProductTable::class . '] component.');
});

it("fails asserting a column's configuration for the record it is checked against", function (): void {
    $this->seed($lamp = new Product('Lamp'), $desk = new Product('Desk'));

    $isLamp = static fn (Column $column): bool => $column->getState() === 'Lamp';

    $tester = $this->createLiveComponent(BrowsableProductTable::class)->assertTableColumnExists('name', $isLamp, $lamp);

    expect(fn () => $tester->assertTableColumnExists('name', $isLamp, $desk))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that a column with the name [name] and provided configuration exists on the [' . BrowsableProductTable::class . '] component.');
});
