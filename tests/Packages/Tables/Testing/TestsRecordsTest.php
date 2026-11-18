<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\CodeForm;
use Sylament\Tests\Fixtures\Tables\BrowsableProductTable;
use Sylament\Tests\Fixtures\Tables\ProductTable;
use Sylament\Tests\Fixtures\Tables\RecordsLaneDeletingTable;

it('fails asserting that the table shows a record it no longer shows', function (): void {
    $this->seed(new Product('Kept'), $gone = new Product('Gone'));

    $tester = $this->createLiveComponent(ProductTable::class)->callAction(TestAction::make(DeleteAction::class)->table($gone));

    expect(fn () => $tester->assertCanSeeTableRecords([$gone]))
        ->toThrow(AssertionFailedError::class, "Failed asserting that a table row with key [{$gone->id}] is visible on the [" . ProductTable::class . '] component.');
});

it('fails asserting that the table does not show a record it shows', function (): void {
    $this->seed($kept = new Product('Kept'));

    expect(fn () => $this->createLiveComponent(ProductTable::class)->assertCanNotSeeTableRecords([$kept]))
        ->toThrow(AssertionFailedError::class, "Failed asserting that a table row with key [{$kept->id}] is not visible on the [" . ProductTable::class . '] component.');
});

it('fails asserting records in an order the table does not show them in', function (): void {
    $this->seed($second = new Product('Beta'), $first = new Product('Alpha'));

    $tester = $this->createLiveComponent(ProductTable::class)->assertCanSeeTableRecords([$first, $second], inOrder: true);

    expect(fn () => $tester->assertCanSeeTableRecords([$second, $first], inOrder: true))
        ->toThrow(AssertionFailedError::class, "Failed asserting that table rows with keys [{$second->id}, {$first->id}] are visible in that order on the [" . ProductTable::class . '] component.');
});

it('fails asserting a total the table does not count', function (): void {
    $this->seed(new Product('Lamp'), new Product('Desk'));

    $tester = $this->createLiveComponent(ProductTable::class)->assertCountTableRecords(2);

    expect(fn () => $tester->assertCountTableRecords(3))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that the [' . ProductTable::class . '] table has [3] records in total.');
});

it('loads a table that defers loading', function (): void {
    $this->seed($lamp = new Product('Lamp'));

    $this->createLiveComponent(BrowsableProductTable::class, ['defersLoading' => true])
        ->loadTable()
        ->assertCanSeeTableRecords([$lamp]);
});

it('reads a record given as its key, a number or a string, or as an array record, by the key its row carries', function (): void {
    $this->seed($lamp = new Product('Lamp'));

    $tester = $this->createLiveComponent(ProductTable::class)->assertCanSeeTableRecords([$lamp->id]);

    expect(fn () => $tester->assertCanNotSeeTableRecords([(string) $lamp->id]))
        ->toThrow(AssertionFailedError::class, "Failed asserting that a table row with key [{$lamp->id}] is not visible on the [" . ProductTable::class . '] component.');

    $tester = $this->createLiveComponent(RecordsLaneDeletingTable::class)->assertCanSeeTableRecords([['__key' => 'lamp', 'name' => 'Lamp']]);

    expect(fn () => $tester->assertCanNotSeeTableRecords([['__key' => 'vase', 'name' => 'Vase']]))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that a table row with key [vase] is not visible on the [' . RecordsLaneDeletingTable::class . '] component.');
});

it('sees a record only where a row carries its whole key', function (): void {
    $this->createLiveComponent(RecordsLaneDeletingTable::class)
        ->assertCanSeeTableRecords(['lamp'])
        ->assertCanNotSeeTableRecords(['lam']);
});

it('sees records in order with other rows between them', function (): void {
    $this->seed($gamma = new Product('Gamma'), $alpha = new Product('Alpha'), new Product('Beta'));

    $this->createLiveComponent(ProductTable::class)->assertCanSeeTableRecords([$alpha, $gamma], inOrder: true);
});

it('refuses a component that renders no table, naming the contract it lacks', function (): void {
    expect(fn () => $this->createLiveComponent(CodeForm::class)->assertCanSeeTableRecords([]))
        ->toThrow(LogicException::class, 'The [' . CodeForm::class . '] component renders no table: it does not implement [Sylament\Tables\Contracts\InteractsWithTable].');
});
