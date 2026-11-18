<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\EditableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\IdentifierCellsTable;
use Sylament\Tests\Fixtures\Tables\RecordsLaneCellsTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

describe('the write', function (): void {
    it('writes a blank post, empty or only spaces, as null', function (string $input): void {
        $this->createLiveComponent(RecordsLaneCellsTable::class)
            ->call('updateTableColumnState', ['column' => 'note', 'record' => 'north', 'input' => $input]);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['before note of north: NULL', 'write note of north: NULL', 'after note of north: NULL']);
    })->with([
        'empty' => [''],
        'only spaces' => ['   '],
    ]);

    it("hands the row's record to a closure that replaces the write, and runs the after hook once it returns", function (): void {
        $this->createLiveComponent(RecordsLaneCellsTable::class)
            ->call('updateTableColumnState', ['column' => 'note', 'record' => 'south', 'input' => 'Wet']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(["before note of south: 'Wet'", "write note of south: 'Wet'", "after note of south: 'Wet'"]);
    });

    it("runs the column's casts over the post before its before hook and its write see it", function (): void {
        $this->createLiveComponent(RecordsLaneCellsTable::class)
            ->call('updateTableColumnState', ['column' => 'stamp', 'record' => 'north', 'input' => 'Fresh']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(["before stamp of north: 'Fresh first:get'", "write stamp of north: 'Fresh first:get'"]);
    });

    it('writes the record and flushes it before the after hook runs', function (): void {
        $this->seed($jar = new LarderJar('Plum', position: 1));

        $this->createLiveComponent(EditableLarderJarTable::class)
            ->call('updateTableColumnState', ['column' => 'label', 'record' => (string) $jar->id, 'input' => 'Pear']);

        expect($this->reload($jar)->label)->toBe('Pear')
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe(["before the label of Plum: 'Pear' replaces 'Plum'", "after the label: the row reads 'Pear'"]);
    });

    it('writes a dotted path onto the far side of the to-one it walks', function (): void {
        $this->seed($supplier = new Product('Acme'), $jar = new LarderJar('Plum', supplier: $supplier, position: 1));

        $crawler = $this->createLiveComponent(EditableLarderJarTable::class)
            ->call('updateTableColumnState', ['column' => 'supplier.name', 'record' => (string) $jar->id, 'input' => 'Bolt'])
            ->render()
            ->crawler();

        expect($this->reload($supplier)->name)->toBe('Bolt')
            ->and($crawler)->toContainElement(Selectors::cell((string) $jar->id, 'supplier.name') . ' ' . Selectors::editableCellState('Bolt'));
    });

    it('refuses a path ending in a collection under its cell, naming the column, and answers 200', function (): void {
        $larder = new Larder('Cellar');
        $larder->addJar($jar = new LarderJar('Plum', position: 1));
        $this->seed($larder);

        $tester = $this->createLiveComponent(EditableLarderJarTable::class)
            ->call('updateTableColumnState', ['column' => 'larder.jars', 'record' => (string) $jar->id, 'input' => 'Fig']);

        expect($tester->response()->getStatusCode())->toBe(200)
            ->and($tester->render()->crawler()->filter(Selectors::cell((string) $jar->id, 'larder.jars') . ' ' . Selectors::CELL_ERROR)->text())
            ->toStartWith('The editable column [larder.jars] cannot write to its record: ');
    });

    it('throws for a records() row it has nowhere to write, which updateStateUsing() makes editable', function (): void {
        $tester = $this->createLiveComponent(RecordsLaneCellsTable::class);

        expect(fn () => $tester->call('updateTableColumnState', ['column' => 'title', 'record' => 'north', 'input' => 'Upper shelf']))
            ->toThrow(LogicException::class, 'The editable column [title] has no record to write to on the records() lane; give it updateStateUsing().');
    });
});

describe('a changed identifier', function (): void {
    it("refuses a changed key half as the cell's own error through the table's own reader, keeping the row's key and writing nothing, while the same key posted as a string is no change", function (string $lineClass, object $line): void {
        $this->seed($line);

        $tester = $this->createLiveComponent(IdentifierCellsTable::class, ['lineClass' => $lineClass]);
        $key = $tester->component()->getTableRecordKey($line);

        $crawler = $tester->call('updateTableColumnState', ['column' => 'line', 'record' => $key, 'input' => '2'])->render()->crawler();

        expect($crawler->filter(Selectors::CELL_ERROR))->toHaveCount(0)
            ->and($crawler)->toContainElement(Selectors::cell($key, 'line') . ' ' . Selectors::editableCellState('2'));

        $crawler = $tester->call('updateTableColumnState', ['column' => 'line', 'record' => $key, 'input' => '5'])->render()->crawler();

        expect($crawler)->toContainElement(Selectors::cell($key, 'line') . ' ' . Selectors::CELL_ERROR, 'This value cannot be changed.')
            ->and($crawler)->toContainElement(Selectors::row($key))
            ->and(preg_grep('/^(UPDATE|INSERT|DELETE) |"START TRANSACTION"/', $this->statementsRun()))->toBe([])
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe(["key check on a {$lineClass} by the reader rebound to the registry's manager"])
            ->and($this->reload($line)->note)->toBe('Kept');

        $tester->call('updateTableColumnState', ['column' => 'note', 'record' => $key, 'input' => 'Moved']);

        expect(preg_grep('/^UPDATE \w+ SET note = /', $this->statementsRun()))->toHaveCount(1)
            ->and($this->reload($line)->note)->toBe('Moved');
    })->with([
        'scalar fields' => fn (): array => [OrderLine::class, new OrderLine(order: 7, line: 2, note: 'Kept')],
        'an association plus a scalar' => fn (): array => [InvoiceLine::class, new InvoiceLine(new Invoice(7), line: 2, note: 'Kept')],
    ]);

    it('refuses a select over the association half by the type its record holds', function (): void {
        $this->seed($line = new InvoiceLine(new Invoice(7), line: 2, note: 'Kept'), new Invoice(8));

        $tester = $this->createLiveComponent(IdentifierCellsTable::class, ['lineClass' => InvoiceLine::class]);
        $key = $tester->component()->getTableRecordKey($line);

        $crawler = $tester->call('updateTableColumnState', ['column' => 'invoice', 'record' => $key, 'input' => '8'])->render()->crawler();

        expect($crawler->filter(Selectors::cell($key, 'invoice') . ' ' . Selectors::CELL_ERROR)->text())->toStartWith('The editable column [invoice] cannot write to its record: ')
            ->and($crawler)->toContainElement(Selectors::row($key));
    });
});

describe('the client state', function (): void {
    it("publishes the record's own state as a string, an empty one for null, and a backed enum by its value", function (): void {
        $this->seed($jar = new LarderJar('Plum', seal: JarSeal::Screw, position: 3));
        $key = (string) $jar->id;

        $crawler = $this->createLiveComponent(EditableLarderJarTable::class)->render()->crawler();

        expect($crawler)->toContainElement(Selectors::cell($key, 'label') . ' ' . Selectors::editableCellState('Plum'))
            ->and($crawler)->toContainElement(Selectors::cell($key, 'position') . ' ' . Selectors::editableCellState('3'))
            ->and($crawler)->toContainElement(Selectors::cell($key, 'sealedOn') . ' ' . Selectors::editableCellState(''))
            ->and($crawler)->toContainElement(Selectors::cell($key, 'seal') . ' ' . Selectors::editableCellState('screw'));
    });

    it('publishes a refused input as it was posted, never running it back through the casts the record state runs through', function (): void {
        $tester = $this->createLiveComponent(RecordsLaneCellsTable::class);

        expect($tester->render()->crawler())->toContainElement(Selectors::cell('north', 'stamp') . ' ' . Selectors::editableCellState('Old first:set'));

        $crawler = $tester->call('updateTableColumnState', ['column' => 'stamp', 'record' => 'north', 'input' => 'Stalest'])->render()->crawler();

        expect($crawler)->toContainElement(Selectors::cell('north', 'stamp') . ' ' . Selectors::editableCellState('Stalest'))
            ->and($crawler)->toContainElement(Selectors::cell('north', 'stamp') . ' ' . Selectors::CELL_ERROR);
    });
});
