<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Sylament\Tables\Columns\Column;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\BrowsableProductTable;
use Sylament\Tests\Fixtures\Tables\RecordsLaneDeletingTable;
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

it('shows every toggleable column, and hides each again given false', function (): void {
    $this->seed(new Product('Lamp'));

    $tester = $this->createLiveComponent(BrowsableProductTable::class)->toggleAllTableColumns();

    expect($tester->render()->crawler()->filter(Selectors::headerCell('id')))->toHaveCount(1)
        ->and($tester->toggleAllTableColumns(false)->render()->crawler()->filter(Selectors::headerCell('id')))->toHaveCount(0);
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

it('checks a column against a record given as its key or as an array record', function (): void {
    $isLamp = static fn (Column $column): bool => $column->getState() === 'Lamp';

    $tester = $this->createLiveComponent(RecordsLaneDeletingTable::class)
        ->assertTableColumnExists('name', $isLamp, 'lamp')
        ->assertTableColumnExists('name', $isLamp, ['__key' => 'lamp', 'name' => 'Lamp']);

    expect(fn () => $tester->assertTableColumnExists('name', $isLamp, ['__key' => 'vase', 'name' => 'Vase']))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that a column with the name [name] and provided configuration exists on the [' . RecordsLaneDeletingTable::class . '] component.')
        ->and(fn () => $tester->assertTableColumnExists('name', $isLamp, 'urn'))
        ->toThrow(LogicException::class, 'The [' . RecordsLaneDeletingTable::class . '] table has no record with key [urn].');
});
