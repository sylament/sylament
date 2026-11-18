<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\EditableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\RecordsLaneCellsTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

describe('callTableColumnAction', function (): void {
    it("runs a column's action closure for the record the key names, answering with the re-render alone", function (): void {
        $tester = $this->createLiveComponent(RecordsLaneCellsTable::class)->callTableColumnAction('opener', 'north');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['open north'])
            ->and($tester->response()->getStatusCode())->toBe(200)
            ->and($tester->response()->getContent())->not->toContain('discarded')
            ->and($tester->render()->crawler())->toContainElement(Selectors::cell('north', 'opener'), 'Open');
    });

    it('runs nothing for a key the table lacks, a column it lacks, a hidden column, or one whose action is an Action or none', function (string $name, string $recordKey): void {
        $tester = $this->createLiveComponent(RecordsLaneCellsTable::class)->call('callTableColumnAction', ['name' => $name, 'recordKey' => $recordKey]);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([])
            ->and($tester->response()->getStatusCode())->toBe(200);

        $tester->callTableColumnAction('opener', 'north');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['open north']);
    })->with([
        'a key the table does not carry' => ['opener', 'west'],
        'a column the table does not declare' => ['closet', 'north'],
        'a hidden column' => ['hiddenOpener', 'north'],
        'an Action' => ['peek', 'north'],
        'no action' => ['plain', 'north'],
    ]);

    it("runs nothing for a record the table's query leaves out", function (): void {
        $this->seed($kept = new LarderJar('Plum', position: 1), $left = new LarderJar('Fig', position: -1));

        $tester = $this->createLiveComponent(EditableLarderJarTable::class)->callTableColumnAction('larder.name', $left);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);

        $tester->callTableColumnAction('larder.name', $kept);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['open Plum']);
    });
});

describe('updateTableColumnState', function (): void {
    it('shows the written value in the same re-render, although a before hook read the value it replaced', function (): void {
        $this->seed($jar = new LarderJar('Plum', position: 1));

        $crawler = $this->createLiveComponent(EditableLarderJarTable::class)
            ->call('updateTableColumnState', ['column' => 'label', 'record' => (string) $jar->id, 'input' => 'Pear'])
            ->render()
            ->crawler();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toContain("before the label of Plum: 'Pear' replaces 'Plum'")
            ->and($crawler->filter(Selectors::cell((string) $jar->id, 'label') . ' input')->attr('value'))->toBe('Pear')
            ->and($crawler)->toContainElement(Selectors::cell((string) $jar->id, 'label') . ' ' . Selectors::editableCellState('Pear'));
    });

    it('writes nothing through a column that is not editable', function (): void {
        $larder = new Larder('Cellar');
        $larder->addJar($jar = new LarderJar('Plum', position: 1));
        $this->seed($larder);

        $tester = $this->createLiveComponent(EditableLarderJarTable::class)
            ->call('updateTableColumnState', ['column' => 'larder.name', 'record' => (string) $jar->id, 'input' => 'Attic']);

        expect($this->reload($larder)->name)->toBe('Cellar')
            ->and($tester->response()->getStatusCode())->toBe(200);

        $tester->call('updateTableColumnState', ['column' => 'label', 'record' => (string) $jar->id, 'input' => 'Pear']);

        expect($this->reload($jar)->label)->toBe('Pear');
    });

    it("writes nothing for a record the table's query leaves out", function (): void {
        $this->seed($kept = new LarderJar('Plum', position: 1), $left = new LarderJar('Fig', position: -1));

        $tester = $this->createLiveComponent(EditableLarderJarTable::class)
            ->call('updateTableColumnState', ['column' => 'label', 'record' => (string) $left->id, 'input' => 'Pear'])
            ->call('updateTableColumnState', ['column' => 'label', 'record' => (string) $kept->id, 'input' => 'Quince']);

        expect($this->reload($left)->label)->toBe('Fig')
            ->and($this->reload($kept)->label)->toBe('Quince')
            ->and($tester->render()->crawler()->filter(Selectors::CELL_ERROR))->toHaveCount(0);
    });

    it('writes a JSON null as null, so a nullable cell clears while a non-nullable one is refused', function (): void {
        $this->seed($jar = new LarderJar('Plum', seal: JarSeal::Screw, position: 1));

        $tester = $this->createLiveComponent(EditableLarderJarTable::class)
            ->call('updateTableColumnState', ['column' => 'seal', 'record' => (string) $jar->id, 'input' => null]);

        expect($this->reload($jar)->seal)->toBeNull();

        $tester->call('updateTableColumnState', ['column' => 'label', 'record' => (string) $jar->id, 'input' => null]);

        expect($this->reload($jar)->label)->toBe('Plum')
            ->and($tester->render()->crawler())->toContainElement(Selectors::cell((string) $jar->id, 'label') . ' ' . Selectors::CELL_ERROR, 'This value should not be blank.');
    });

    it("judges the row it was handed, so a disabled() closure locks one row's cell and leaves its neighbour's writable, with nothing to paint", function (): void {
        $this->seed($waxed = new LarderJar('Fig', seal: JarSeal::Wax, position: 1), $open = new LarderJar('Plum', position: 2));

        $tester = $this->createLiveComponent(EditableLarderJarTable::class)
            ->call('updateTableColumnState', ['column' => 'label', 'record' => (string) $waxed->id, 'input' => 'Pear']);

        expect($this->reload($waxed)->label)->toBe('Fig')
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe([])
            ->and($tester->render()->crawler()->filter(Selectors::cell((string) $waxed->id, 'label') . ' input[disabled]'))->toHaveCount(1)
            ->and($tester->render()->crawler()->filter(Selectors::CELL_ERROR))->toHaveCount(0);

        $tester->call('updateTableColumnState', ['column' => 'label', 'record' => (string) $open->id, 'input' => 'Quince']);

        expect($this->reload($open)->label)->toBe('Quince')
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toContain("before the label of Plum: 'Quince' replaces 'Plum'")
            ->and($tester->render()->crawler()->filter(Selectors::cell((string) $open->id, 'label') . ' input:not([disabled])'))->toHaveCount(1);
    });

    it('refuses a column the developer hid, on either action, while one the user toggled off answers, in whatever order the manager put the columns', function (string $action, array $hidden, array $toggledOff, string $answer): void {
        $tester = $this->createLiveComponent(RecordsLaneCellsTable::class);
        $tester->call('applyTableColumnManager', ['state' => array_reverse($tester->component()->tableColumns)]);

        $headers = $tester->render()->crawler()->filter(Selectors::TABLE . ' th')->each(static fn (Crawler $header): string => $header->text());

        expect(array_search('Plain', $headers, true))->toBeLessThan(array_search('Title', $headers, true));

        $tester->call($action, $hidden);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);

        $tester->call($action, $toggledOff);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([$answer])
            ->and($tester->render()->crawler()->filter(Selectors::headerCell('aside')))->toHaveCount(0);
    })->with([
        'the write' => ['updateTableColumnState', ['column' => 'secret', 'record' => 'north', 'input' => 'Spare'], ['column' => 'aside', 'record' => 'north', 'input' => 'Spare'], "write aside of north: 'Spare'"],
        'the column action' => ['callTableColumnAction', ['name' => 'hiddenOpener', 'recordKey' => 'north'], ['name' => 'shyOpener', 'recordKey' => 'north'], 'open shy north'],
    ]);
});

describe('the refusal', function (): void {
    it("paints a refused write under its own cell alone, echoing the input, and the next request starts clean", function (): void {
        $this->seed($fig = new LarderJar('Fig', position: 1), $plum = new LarderJar('Plum', position: 2));
        $figLabel = Selectors::cell((string) $fig->id, 'label');

        $tester = $this->createLiveComponent(EditableLarderJarTable::class)
            ->call('updateTableColumnState', ['column' => 'label', 'record' => (string) $fig->id, 'input' => 'A fig far too long']);
        $crawler = $tester->render()->crawler();

        expect($this->reload($fig)->label)->toBe('Fig')
            ->and($tester->response()->getStatusCode())->toBe(200)
            ->and($crawler)->toContainElement($figLabel . ' ' . Selectors::CELL_ERROR, 'Keep the label to 12 characters.')
            ->and($crawler)->toContainElement($figLabel . ' ' . Selectors::editableCellState('A fig far too long'))
            ->and($crawler->filter($figLabel . ' input')->attr('value'))->toBe('A fig far too long')
            ->and($crawler->filter(Selectors::CELL_ERROR))->toHaveCount(1);

        $crawler = $tester->call('updateTableColumnState', ['column' => 'label', 'record' => (string) $plum->id, 'input' => 'Quince'])->render()->crawler();

        expect($crawler->filter(Selectors::CELL_ERROR))->toHaveCount(0)
            ->and($crawler)->toContainElement($figLabel . ' ' . Selectors::editableCellState('Fig'))
            ->and($crawler->filter($figLabel . ' input')->attr('value'))->toBe('Fig');
    });

    it('files a write the record turns down under the cell, answering 200', function (): void {
        $this->seed($jar = new LarderJar('Plum', position: 1));

        $tester = $this->createLiveComponent(EditableLarderJarTable::class)
            ->call('updateTableColumnState', ['column' => 'sealedOn', 'record' => (string) $jar->id, 'input' => 'next spring']);

        expect($tester->response()->getStatusCode())->toBe(200)
            ->and($this->reload($jar)->sealedOn)->toBeNull()
            ->and($tester->render()->crawler()->filter(Selectors::cell((string) $jar->id, 'sealedOn') . ' ' . Selectors::CELL_ERROR)->text())
            ->toStartWith('The editable column [sealedOn] cannot write to its record: ');
    });

    it("lets an exception the application's own write closure throws propagate", function (): void {
        $tester = $this->createLiveComponent(RecordsLaneCellsTable::class);

        expect(fn () => $tester->call('updateTableColumnState', ['column' => 'note', 'record' => 'north', 'input' => 'explode']))
            ->toThrow(RuntimeException::class, 'The ledger is locked.');
    });
});
