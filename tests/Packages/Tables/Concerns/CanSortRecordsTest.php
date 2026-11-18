<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\CodeKeyedSlate;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\LockableReorderLarderJarTable;
use Sylament\Tests\Fixtures\Tables\NoteSortedLineTable;
use Sylament\Tests\Fixtures\Tables\ReplacingDefaultSortProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SortableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\SortInjectingRecordsTable;
use Sylament\Tests\Fixtures\Tables\TablePageController;

describe('the sort state', function (): void {
    it("shows on the sorted column's header, a bare column as ascending, and none on the other sortable columns", function (string $sort, string $state): void {
        $this->seed(new LarderJar('Fig'));

        $page = $this->createLiveComponent(SortableLarderJarTable::class, ['tableSort' => $sort])->render()->crawler();

        expect($page)->toContainElement(Selectors::sortState('label', $state))
            ->toContainElement(Selectors::sortState('position', 'none'))
            ->toContainElement(Selectors::sortTrigger('label'))
            ->and($page->filter(Selectors::headerCell('seal')))->toHaveCount(1)
            ->and($page->filter(Selectors::headerCell('seal') . '[aria-sort]'))->toHaveCount(0)
            ->and($page->filter(Selectors::sortTrigger('seal')))->toHaveCount(0);
    })->with([
        'descending' => ['label:desc', 'descending'],
        'a bare column' => ['label', 'ascending'],
    ]);

    it('hands a records closure a seeded sort only when the table offers it', function (string $sort, string $handed): void {
        $this->createLiveComponent(SortInjectingRecordsTable::class, ['tableSort' => $sort])->render();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([$handed]);
    })->with([
        'a sortable column' => ['label:desc', 'sort label desc'],
        'an undeclared column' => ['nope:asc', 'sort  '],
        'an unsortable column' => ['note:asc', 'sort  '],
        'a hidden column' => ['secret:asc', 'sort  '],
        'an unknown direction' => ['label:sideways', 'sort  '],
    ]);

    it('clears a remembered sort naming a column the table no longer offers', function (array $mount, string $column, string $handed): void {
        $client = $this::getContainer()->get('test.client');

        $this->createLiveComponent(SortInjectingRecordsTable::class, $mount, client: $client)->sortTable($column, 'desc');
        $this->visit(TablePageController::url('Tables:SortInjectingRecordsTable'), client: $client);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([$handed]);
    })->with([
        'a column it still offers' => [[], 'label', 'sort label desc'],
        'a column it offered then' => [['offersShelf' => true], 'shelf', 'sort  '],
    ]);
});

it('cycles the clicked column ascending, descending and unsorted, starts another one ascending, takes a direction as given and returns to page one', function (): void {
    $this->seed(
        $damson = new LarderJar('Damson', position: 1),
        $apple = new LarderJar('Apple', position: 2),
        $cherry = new LarderJar('Cherry', position: 3),
        $bramble = new LarderJar('Bramble', position: 4),
    );

    $tester = $this->createLiveComponent(SortableLarderJarTable::class, ['variants' => ['paged']])
        ->call('gotoTablePage', ['page' => 2])
        ->assertCanSeeTableRecords([$cherry, $bramble], inOrder: true)
        ->sortTable('label')
        ->assertCanSeeTableRecords([$apple, $bramble], inOrder: true)
        ->assertCanNotSeeTableRecords([$cherry, $damson]);

    expect($tester->render()->crawler())->toContainElement(Selectors::sortState('label', 'ascending'));

    $tester->sortTable('label')->assertCanSeeTableRecords([$damson, $cherry], inOrder: true);

    expect($tester->render()->crawler())->toContainElement(Selectors::sortState('label', 'descending'));

    $tester->sortTable('label')->assertCanSeeTableRecords([$damson, $apple], inOrder: true);

    expect($tester->render()->crawler())->toContainElement(Selectors::sortState('label', 'none'));

    $tester->sortTable('label')->sortTable('label')->sortTable('position')->assertCanSeeTableRecords([$damson, $apple], inOrder: true);

    expect($tester->render()->crawler())->toContainElement(Selectors::sortState('position', 'ascending'))
        ->toContainElement(Selectors::sortState('label', 'none'));

    $tester->sortTable('label', 'desc')->sortTable('label', 'desc')->assertCanSeeTableRecords([$damson, $cherry], inOrder: true);

    expect($tester->render()->crawler())->toContainElement(Selectors::sortState('label', 'descending'));
});

it('ignores a sort naming a column the table does not offer or a direction it does not know, and takes a column named by an integer', function (): void {
    $hooks = fn (): array => $this::getContainer()->get(HookLog::class)->hooks;

    $tester = $this->createLiveComponent(SortInjectingRecordsTable::class)->sortTable('nope');

    expect($hooks())->toBe(['sort  ']);

    $tester->sortTable('note');

    expect($hooks())->toBe(['sort  ']);

    $tester->sortTable('label', 'sideways');

    expect($hooks())->toBe(['sort  ']);

    $tester->call('sortTable', ['column' => 2024]);

    expect($hooks())->toBe(['sort 2024 asc']);
});

describe('the order composed onto the query', function (): void {
    it('keeps an order the declared query carries ahead of the sort the user clicks', function (): void {
        $this->seed($figEarly = new LarderJar('Fig', position: 1), $figLate = new LarderJar('Fig', position: 2), $apple = new LarderJar('Apple'));

        $this->createLiveComponent(SortableLarderJarTable::class, ['variants' => ['orderedQuery']])
            ->sortTable('position', 'desc')
            ->assertCanSeeTableRecords([$apple, $figLate, $figEarly], inOrder: true);
    });

    it('hands a default-sort closure the builder and the direction it declares', function (): void {
        $this->seed($first = new LarderJar('Fig', position: 1), $third = new LarderJar('Fig', position: 3), $second = new LarderJar('Fig', position: 2));

        $this->createLiveComponent(SortableLarderJarTable::class, ['variants' => ['closureDefaultSort']])
            ->assertCanSeeTableRecords([$third, $second, $first], inOrder: true);
    });

    it('lists the builder a default-sort closure returns in place of the query, without its search or sort, tiebroken by its own key', function (): void {
        $this->seed(new Product('Lamp'), $bee = new CodeKeyedSlate('bee', 'listed'), $ant = new CodeKeyedSlate('ant', 'listed too'), $cat = new CodeKeyedSlate('cat', 'hidden'));

        $this->createLiveComponent(ReplacingDefaultSortProductTable::class)
            ->searchTable('Lamp')
            ->sortTable('name', 'desc')
            ->assertCanSeeTableRecords([$ant, $bee], inOrder: true)
            ->assertCanNotSeeTableRecords([$cat]);
    });

    it("applies no order but the group's under groupsOnly()", function (): void {
        $this->seed(new LarderJar('Fig'));

        $this->createLiveComponent(SortableLarderJarTable::class, ['variants' => ['groupsOnly']])->sortTable('label', 'desc');

        $records = array_values(array_filter($this->statementsRun(), static fn (string $sql): bool => str_starts_with($sql, 'SELECT a0_.id AS id_0')));

        expect($records)->toHaveCount(1)
            ->and($records[0])->toEndWith('FROM LarderJar a0_ ORDER BY a0_.seal ASC');
    });

    it('orders a reordering table by its reorder column alone', function (): void {
        $this->seed($second = new LarderJar('Apple', position: 2), $first = new LarderJar('Banana', position: 1));

        $this->createLiveComponent(LockableReorderLarderJarTable::class)
            ->sortTable('label')
            ->assertCanSeeTableRecords([$second, $first], inOrder: true)
            ->call('toggleTableReordering')
            ->assertCanSeeTableRecords([$first, $second], inOrder: true);
    });

    it("leaves a records closure's rows in the order it returns them", function (): void {
        $this->createLiveComponent(SortInjectingRecordsTable::class)
            ->sortTable('label')
            ->assertCanSeeTableRecords(['plum', 'fig', 'quince'], inOrder: true);
    });

    it('tiebreaks rows of an equal sorted value by every field of a composite key', function (string $lineClass, object $last, object $middle, object $first): void {
        $this->seed($first, $middle, $last);

        $this->createLiveComponent(NoteSortedLineTable::class, ['lineClass' => $lineClass])
            ->sortTable('note', 'desc')
            ->assertCanSeeTableRecords([$last, $middle, $first], inOrder: true);
    })->with([
        'scalar fields' => fn (): array => [OrderLine::class, new OrderLine(order: 8, line: 1, note: 'tied'), new OrderLine(order: 7, line: 3, note: 'tied'), new OrderLine(order: 7, line: 2, note: 'tied')],
        'an association plus a scalar' => fn (): array => [InvoiceLine::class, new InvoiceLine(new Invoice(8), line: 1, note: 'tied'), new InvoiceLine($seven = new Invoice(7), line: 3, note: 'tied'), new InvoiceLine($seven, line: 2, note: 'tied')],
    ]);

    it('tiebreaks rows sharing a related record by the key when the user sorts by the related id', function (): void {
        $this->seed(
            $lower = new Product('Acorn'),
            $higher = new Product('Zest'),
            $lowerFirst = new LarderJar('Fig', supplier: $lower),
            $higherFirst = new LarderJar('Fig', supplier: $higher),
            $lowerSecond = new LarderJar('Fig', supplier: $lower),
            $higherSecond = new LarderJar('Fig', supplier: $higher),
        );

        $this->createLiveComponent(SortableLarderJarTable::class, ['variants' => ['noDefaultSort']])
            ->sortTable('supplier.id', 'desc')
            ->assertCanSeeTableRecords([$higherSecond, $higherFirst, $lowerSecond, $lowerFirst], inOrder: true);
    });
});
