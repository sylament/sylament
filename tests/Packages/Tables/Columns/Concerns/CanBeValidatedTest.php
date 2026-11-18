<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\ColumnKindSample;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\EditableFlaggedSampleTable;
use Sylament\Tests\Fixtures\Tables\EditableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\RecordsLaneCellsTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

describe('declared constraints', function (): void {
    it('refuses a post through each constraint the column declares, in the message the developer gave it, and writes one they all accept', function (): void {
        $tester = $this->createLiveComponent(RecordsLaneCellsTable::class);
        $error = Selectors::cell('north', 'code') . ' ' . Selectors::CELL_ERROR;

        expect($tester->call('updateTableColumnState', ['column' => 'code', 'record' => 'north', 'input' => 'ABCDEF'])->render()->crawler())->toContainElement($error, 'Keep the code to 4 letters.')
            ->and($tester->call('updateTableColumnState', ['column' => 'code', 'record' => 'north', 'input' => 'ab'])->render()->crawler())->toContainElement($error, 'Write the code in capitals.')
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);

        $tester->call('updateTableColumnState', ['column' => 'code', 'record' => 'north', 'input' => 'ABC']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(["write code of north: 'ABC'"]);
    });

    it("applies a constraint only where its condition holds for the row's record", function (): void {
        $tester = $this->createLiveComponent(RecordsLaneCellsTable::class)
            ->call('updateTableColumnState', ['column' => 'shelf', 'record' => 'north', 'input' => '']);

        expect($tester->render()->crawler())->toContainElement(Selectors::cell('north', 'shelf') . ' ' . Selectors::CELL_ERROR, 'Name the shelf.');

        $tester->call('updateTableColumnState', ['column' => 'shelf', 'record' => 'south', 'input' => '']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['write shelf of south: NULL']);
    });

    it('throws, naming the column, for a message whose option its constraint lacks', function (): void {
        $tester = $this->createLiveComponent(RecordsLaneCellsTable::class);

        expect(fn () => $tester->call('updateTableColumnState', ['column' => 'motto', 'record' => 'north', 'input' => 'Onward']))
            ->toThrow(LogicException::class, 'Sylament cannot apply a validation message to the column [motto] because Symfony\Component\Validator\Constraints\Length has no [message] message option. Use one of: [maxMessage], [minMessage], [exactMessage], [charsetMessage].');
    });
});

describe("the record's own type", function (): void {
    it('refuses a blank against a property that rejects null, unless the column declares a presence constraint of its own', function (): void {
        $this->seed($jar = new LarderJar('Plum', position: 1));
        $key = (string) $jar->id;

        $tester = $this->createLiveComponent(EditableLarderJarTable::class);

        expect($tester->call('updateTableColumnState', ['column' => 'label', 'record' => $key, 'input' => ''])->render()->crawler())
            ->toContainElement(Selectors::cell($key, 'label') . ' ' . Selectors::CELL_ERROR, 'This value should not be blank.')
            ->and($tester->call('updateTableColumnState', ['column' => 'position', 'record' => $key, 'input' => ''])->render()->crawler())
            ->toContainElement(Selectors::cell($key, 'position') . ' ' . Selectors::CELL_ERROR, 'Number the jar.')
            ->and($this->reload($jar))->toBeRecord(label: 'Plum', position: 1);
    });

    it('seeds nothing on a records() lane, which has no record type to read', function (): void {
        $this->createLiveComponent(RecordsLaneCellsTable::class)
            ->call('updateTableColumnState', ['column' => 'note', 'record' => 'north', 'input' => ['Dry', 'Cool']]);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toContain('write note of north: ' . var_export(['Dry', 'Cool'], true));
    });

    it('refuses what an integer property cannot hold and coerces a numeric post onto it, and refuses a non-string against a string property', function (): void {
        $this->seed($jar = new LarderJar('Plum', position: 1));
        $key = (string) $jar->id;

        $tester = $this->createLiveComponent(EditableLarderJarTable::class);

        expect($tester->call('updateTableColumnState', ['column' => 'position', 'record' => $key, 'input' => 'many'])->render()->crawler())
            ->toContainElement(Selectors::cell($key, 'position') . ' ' . Selectors::CELL_ERROR, 'This value should be of type numeric.')
            ->and($tester->call('updateTableColumnState', ['column' => 'label', 'record' => $key, 'input' => ['Fig']])->render()->crawler())
            ->toContainElement(Selectors::cell($key, 'label') . ' ' . Selectors::CELL_ERROR, 'This value should be of type string.')
            ->and($this->reload($jar))->toBeRecord(label: 'Plum', position: 1);

        $tester->call('updateTableColumnState', ['column' => 'position', 'record' => $key, 'input' => '7']);

        expect($this->reload($jar)->position)->toBe(7);
    });

    it('lets a type the column declares stand in for the one the property would seed', function (): void {
        $this->seed($supplier = new Product('Acme'), $jar = new LarderJar('Plum', supplier: $supplier, position: 1));
        $key = (string) $jar->id;

        $tester = $this->createLiveComponent(EditableLarderJarTable::class)
            ->call('updateTableColumnState', ['column' => 'supplier.name', 'record' => $key, 'input' => ['Bolt']]);

        expect($tester->render()->crawler())->toContainElement(Selectors::cell($key, 'supplier.name') . ' ' . Selectors::CELL_ERROR, 'Name the supplier plainly.')
            ->and($this->reload($supplier)->name)->toBe('Acme');

        $tester->call('updateTableColumnState', ['column' => 'supplier.name', 'record' => $key, 'input' => 5]);

        expect($this->reload($supplier)->name)->toBe('5');
    });

    it('seeds no type for a boolean property, so a column posting strings still writes it', function (): void {
        $this->seed($sample = new ColumnKindSample('Rinsed'));

        $tester = $this->createLiveComponent(EditableFlaggedSampleTable::class, ['control' => 'select'])
            ->call('updateTableColumnState', ['column' => 'flagged', 'record' => (string) $sample->id, 'input' => '1']);

        expect($this->reload($sample)->flagged)->toBeTrue()
            ->and($tester->render()->crawler()->filter(Selectors::CELL_ERROR))->toHaveCount(0);

        $tester->call('updateTableColumnState', ['column' => 'flagged', 'record' => (string) $sample->id, 'input' => '0']);

        expect($this->reload($sample)->flagged)->toBeFalse();
    });
});
