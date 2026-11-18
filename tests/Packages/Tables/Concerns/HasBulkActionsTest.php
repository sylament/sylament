<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Tables\Testing\Testable;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\TariffBand;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\BatchedSelectionLineTable;
use Sylament\Tests\Fixtures\Tables\ConfiguredSelectionProductTable;
use Sylament\Tests\Fixtures\Tables\FetchJoinedLarderTable;
use Sylament\Tests\Fixtures\Tables\GroupedSelectionLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

describe('reading the selection', function (): void {
    it('hands a bulk action the selected records or their keys, and while tracking every row but the deselected ones, each in one statement', function (): void {
        $this->seed($chair = new Product('Chair'), $lamp = new Product('Lamp'), $vase = new Product('Vase'));

        $tester = $this->createLiveComponent(ConfiguredSelectionProductTable::class)->selectTableRecords([$lamp, $vase])->callAction(TestAction::make('archive')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['archive read Lamp, Vase'])
            ->and(preg_grep('/ WHERE /', $this->statementsRun()))->toHaveCount(1);

        $tester->callAction(TestAction::make('archiveByKey')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(["archiveByKey read {$lamp->id}, {$vase->id}"]);

        $tracked = $this->createLiveComponent(ConfiguredSelectionProductTable::class)
            ->set('isTrackingDeselectedTableRecords', true)
            ->set('deselectedTableRecords', [(string) $vase->id])
            ->callAction(TestAction::make('archive')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['archive read Chair, Lamp'])
            ->and(preg_grep('/ WHERE /', $this->statementsRun()))->toHaveCount(1)
            ->and($tracked->render()->crawler()->filter(Selectors::SELECTED_RECORD)->each(static fn ($checkbox): ?string => $checkbox->attr('value')))->toBe([(string) $chair->id, (string) $lamp->id]);
    });

    it('selects every row while tracking an empty exclusion, binding no key at all', function (): void {
        $this->seed($chair = new Product('Chair'), new Product('Lamp'));

        $tester = $this->createLiveComponent(ConfiguredSelectionProductTable::class)
            ->set('isTrackingDeselectedTableRecords', true)
            ->callAction(TestAction::make('archive')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['archive read Chair, Lamp'])
            ->and(preg_grep('/\bIN\b/', $this->statementsRun()))->toBe([]);

        $tester->set('deselectedTableRecords', [(string) $chair->id])->callAction(TestAction::make('archive')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['archive read Lamp'])
            ->and(preg_grep('/\bIN\b/', $this->statementsRun()))->toHaveCount(1);
    });

    it("resolves a records() table's selection off its rows, so a key they lack reads as nothing while the banner still shows it, and a tracked one as every row but the deselected", function (): void {
        $tester = $this->createLiveComponent(ConfiguredSelectionProductTable::class, ['records' => true])->selectTableRecords(['attic']);

        expect($tester->render()->crawler()->filter(Selectors::SELECTION_BANNER . ':not([hidden])'))->toHaveCount(1);

        $tester->callAction(TestAction::make('archive')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['archive read ']);

        $this->createLiveComponent(ConfiguredSelectionProductTable::class, ['records' => true])
            ->set('isTrackingDeselectedTableRecords', true)
            ->set('deselectedTableRecords', ['lamp'])
            ->callAction(TestAction::make('archive')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['archive read Chair, Desk, Rug, Sofa, Vase']);
    });
});

describe('what clears a selection', function (): void {
    it('clears the selection on a filter change, a search, a column search and clearing the search', function (Closure $change): void {
        $this->seed($lamp = new Product('Lamp'), new Product('Vase'));

        $tester = $this->createLiveComponent(ConfiguredSelectionProductTable::class)->selectTableRecords([$lamp]);

        expect($tester->render()->crawler()->filter(Selectors::SELECTED_RECORD))->toHaveCount(1);

        $change($tester);

        expect($tester->render()->crawler()->filter(Selectors::row((string) $lamp->id)))->toHaveCount(1)
            ->and($tester->render()->crawler()->filter(Selectors::SELECTED_RECORD))->toHaveCount(0);

        $tester->callAction(TestAction::make('archive')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['archive read ']);
    })->with([
        'a filter' => [static fn (Testable $tester): Testable => $tester->filterTable('lit')],
        'a search' => [static fn (Testable $tester): Testable => $tester->searchTable('a')],
        'a column search' => [static fn (Testable $tester): Testable => $tester->searchTableColumns(['name' => 'a'])],
        'clearing the search' => [static fn (Testable $tester): Testable => $tester->call('resetTableSearch')],
    ]);

    it('keeps the selection through a sort, a page change and a records-per-page change', function (Closure $change): void {
        $this->seed($lamp = new Product('Lamp'), new Product('Vase'));

        $change($this->createLiveComponent(ConfiguredSelectionProductTable::class)->selectTableRecords([$lamp]))
            ->callAction(TestAction::make('archive')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['archive read Lamp']);
    })->with([
        'a sort' => [static fn (Testable $tester): Testable => $tester->sortTable('name', 'desc')],
        'a page change' => [static fn (Testable $tester): Testable => $tester->call('gotoTablePage', ['page' => 2])],
        'a records-per-page change' => [static fn (Testable $tester): Testable => $tester->set('tableRecordsPerPage', 5)],
    ]);
});

describe('never trusting the client', function (): void {
    it('drops a nested list, a bool and a key no record has from the selection, reads a keyed map by its values and casts each key to a string', function (): void {
        $this->seed($lamp = new Product('Lamp'), $rug = new Product('Rug'), $vase = new Product('Vase'));

        $this->createLiveComponent(ConfiguredSelectionProductTable::class)
            ->set('selectedTableRecords', [[(string) $vase->id], true, 'first' => (string) $lamp->id, $rug->id, '999999'])
            ->callAction(TestAction::make('archive')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['archive read Lamp, Rug']);
    });

    it('reads a forged tracking flag as no tracking where the table cannot track or keeps selection to its page', function (array $props, string $read, int $ticked): void {
        $this->seed(new Product('Lamp'), new Product('Vase'));

        $tester = $this->createLiveComponent(ConfiguredSelectionProductTable::class, $props)->set('isTrackingDeselectedTableRecords', true);

        expect($tester->render()->crawler()->filter(Selectors::SELECTED_RECORD))->toHaveCount($ticked);

        $tester->callAction(TestAction::make('archive')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([$read]);
    })->with([
        'a table that tracks' => [[], 'archive read Lamp, Vase', 2],
        'a table that does not track' => [['tracks' => false], 'archive read ', 0],
        'a page-only table' => [['pageOnly' => true], 'archive read ', 0],
    ]);

    it('re-applies the filters and the selectability callback to a selection, the filters only where filtering clears it, and the callback before binding the keys', function (array $props, bool $filtered, string $read, string $readKeys): void {
        $this->seed($lamp = new Product('Lamp'), $vase = new Product('Vase'));

        $tester = $this->createLiveComponent(ConfiguredSelectionProductTable::class, $props);

        if ($filtered) {
            $tester->filterTable('lit');
        }

        $tester->selectTableRecords([$lamp, $vase])->callAction(TestAction::make('archive')->table()->bulk());

        expect(array_values(preg_grep('/^archive/', $this::getContainer()->get(HookLog::class)->hooks)))->toBe([$read]);

        $tester->callAction(TestAction::make('archiveByKey')->table()->bulk());

        expect(array_values(preg_grep('/^archive/', $this::getContainer()->get(HookLog::class)->hooks)))->toBe([sprintf($readKeys, $lamp->id, $vase->id)]);
    })->with([
        'filtered' => [[], true, 'archive read Lamp', 'archiveByKey read %d'],
        'filtered, keeping the selection' => [['keepsSelectionWhenFiltered' => true], true, 'archive read Lamp, Vase', 'archiveByKey read %d, %d'],
        'refusing the vase' => [['refuses' => true], false, 'archive read Lamp', 'archiveByKey read %d'],
    ]);
});

describe('deselecting all', function (): void {
    it('clears the selection on the server, so a read later in the same request finds nothing and the re-render ticks no checkbox', function (Closure $select): void {
        $this->seed(new Product('Chair'), $lamp = new Product('Lamp'), $vase = new Product('Vase'));

        $tester = $select($this->createLiveComponent(ConfiguredSelectionProductTable::class), $lamp, $vase)->callAction(TestAction::make('recount')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['recount read 2, then 0'])
            ->and($tester->render()->crawler()->filter(Selectors::RECORD_CHECKBOX))->toHaveCount(3)
            ->and($tester->render()->crawler()->filter(Selectors::SELECTED_RECORD))->toHaveCount(0);

        $tester->callAction(TestAction::make('archive')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['archive read ']);
    })->with([
        'a selection' => [static fn (Testable $tester, Product $lamp, Product $vase): Testable => $tester->selectTableRecords([$lamp, $vase])],
        'a tracked selection' => [static fn (Testable $tester, Product $lamp, Product $vase): Testable => $tester->set('isTrackingDeselectedTableRecords', true)->set('deselectedTableRecords', [(string) $vase->id])],
    ]);
});

describe('selecting all', function (): void {
    it('writes every selectable key across the pages into the selection of a table that does not track, in one statement, ticking the page it re-renders', function (): void {
        $this->seed(...array_map(static fn (array $row): Product => new Product($row['name']), ConfiguredSelectionProductTable::ROWS));

        $tester = $this->createLiveComponent(ConfiguredSelectionProductTable::class, ['tracks' => false, 'tableRecordsPerPage' => 5])->call('selectAllTableRecords');

        expect(preg_grep('/^SELECT \w+\.id AS \w+ FROM Product \w+$/', $this->statementsRun()))->toHaveCount(1)
            ->and($tester->render()->crawler()->filter(Selectors::SELECTED_RECORD))->toHaveCount(5);

        $tester->callAction(TestAction::make('archive')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['archive read Chair, Desk, Lamp, Rug, Sofa, Vase']);
    });

    it('selects the keys the table lets the user select, the page alone where selection is page-only, ending any tracked selection', function (array $props, string $read): void {
        $this->seed(...array_map(static fn (array $row): Product => new Product($row['name']), ConfiguredSelectionProductTable::ROWS));
        $lamp = $this->entityManager()->getRepository(Product::class)->findOneBy(['name' => 'Lamp']);

        $this->createLiveComponent(ConfiguredSelectionProductTable::class, [...$props, 'tableRecordsPerPage' => 5])
            ->set('isTrackingDeselectedTableRecords', true)
            ->set('deselectedTableRecords', [(string) $lamp?->id])
            ->call('selectAllTableRecords')
            ->callAction(TestAction::make('archive')->table()->bulk());

        expect(array_values(preg_grep('/^archive/', $this::getContainer()->get(HookLog::class)->hooks)))->toBe([$read]);
    })->with([
        'a tracking table' => [[], 'archive read Chair, Desk, Lamp, Rug, Sofa, Vase'],
        'a page-only table' => [['pageOnly' => true], 'archive read Chair, Desk, Lamp, Rug, Sofa'],
        'a table refusing the vase' => [['tracks' => false, 'refuses' => true], 'archive read Chair, Desk, Lamp, Rug, Sofa'],
    ]);

    it("counts the banner's select-all against every selectable record, the page alone where selection is page-only, and the filtered query where simple pagination keeps no total", function (array $props, string $selectAll): void {
        $this->seed(...array_map(static fn (array $row): Product => new Product($row['name']), ConfiguredSelectionProductTable::ROWS));

        $crawler = $this->createLiveComponent(ConfiguredSelectionProductTable::class, [...$props, 'tableRecordsPerPage' => 5])->render()->crawler();

        expect($crawler)->toContainElement(Selectors::SELECTION_BANNER . ' ' . SupportSelectors::LINK, $selectAll);
    })->with([
        'every record' => [[], 'Select all 6'],
        'the page' => [['pageOnly' => true], 'Select all 5'],
        'the selectable records' => [['refuses' => true], 'Select all 5'],
        'every record, simply paginated' => [['simplePagination' => true], 'Select all 6'],
    ]);

    it('selects one key per record off a query fetch-joining a to-many, in one statement', function (): void {
        $cellar = new Larder('Cellar');
        $cellar->addJar(new LarderJar('Fig'));
        $cellar->addJar(new LarderJar('Plum'));

        $attic = new Larder('Attic');
        $attic->addJar(new LarderJar('Pear'));
        $this->seed($cellar, $attic);

        $tester = $this->createLiveComponent(FetchJoinedLarderTable::class)->call('selectAllTableRecords');

        expect(preg_grep('/^SELECT \w+\.id AS \w+ FROM Larder \w+ LEFT JOIN LarderJar \w+ ON [\w.= ]+$/', $this->statementsRun()))->toHaveCount(1)
            ->and($tester->render()->crawler()->filter(Selectors::SELECTED_RECORD))->toHaveCount(2);

        $tester->callAction(TestAction::make('archive')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(["archive read {$cellar->id}, {$attic->id}"]);
    });
});

describe('grouped selection', function (): void {
    it("keys a group's checkbox by the group's records across the pages, one query a group, or by its records on the page where selection is page-only", function (bool $pageOnly, int $queries, bool $wholeZest): void {
        $this->seed(
            $acme = new Product('Acme'),
            $zest = new Product('Zest'),
            $apricot = new LarderJar('Apricot', supplier: $acme),
            $blackberry = new LarderJar('Blackberry', supplier: $acme),
            $date = new LarderJar('Date', supplier: $zest),
            $elder = new LarderJar('Elder', supplier: $zest),
            $fig = new LarderJar('Fig', supplier: $zest),
            $grape = new LarderJar('Grape', supplier: $zest),
        );
        $crawler = $this->createLiveComponent(GroupedSelectionLarderJarTable::class, ['pageOnly' => $pageOnly, 'tableRecordsPerPage' => 5])->render()->crawler();
        $groupKeys = static function (string $group) use ($crawler): array {
            $keys = json_decode((string) $crawler->filter(Selectors::groupHeaderRow($group) . " input[aria-label=\"Select/deselect every record in group {$group} for bulk actions.\"]")->attr('data-sy-group-keys'), flags: JSON_THROW_ON_ERROR);
            sort($keys, SORT_NATURAL);

            return $keys;
        };
        $keys = static fn (LarderJar ...$jars): array => array_map(static fn (LarderJar $jar): string => (string) $jar->id, $jars);

        expect(preg_grep('/^SELECT \w+\.id AS \w+ FROM LarderJar .* WHERE /', $this->statementsRun()))->toHaveCount($queries)
            ->and($groupKeys('Acme'))->toBe($keys($apricot, $blackberry))
            ->and($groupKeys('Zest'))->toBe($wholeZest ? $keys($date, $elder, $fig, $grape) : $keys($date, $elder, $fig));
    })->with([
        'across the pages' => [false, 2, true],
        'page-only' => [true, 0, false],
    ]);

    it('gathers the records with a blank group column in a group of their own', function (): void {
        $this->seed($acme = new Product('Acme'), $apricot = new LarderJar('Apricot', supplier: $acme), $fig = new LarderJar('Fig'), $plum = new LarderJar('Plum'));

        $crawler = $this->createLiveComponent(GroupedSelectionLarderJarTable::class)->render()->crawler();

        $groupKeys = static function (string $group) use ($crawler): array {
            $keys = json_decode((string) $crawler->filter(Selectors::groupHeaderRow($group) . ' input.sy-ta-group-checkbox')->attr('data-sy-group-keys'), flags: JSON_THROW_ON_ERROR);
            sort($keys, SORT_NATURAL);

            return $keys;
        };

        expect($groupKeys(''))->toBe([(string) $fig->id, (string) $plum->id])
            ->and($groupKeys('Acme'))->toBe([(string) $apricot->id]);
    });
});

describe('a selection over several identifier fields', function (): void {
    it('reads a selection larger than one batch in batches of 500 keys, each record once, a malformed key dropped, and its query in one statement', function (Closure $lines): void {
        $seeded = $lines(501);
        $this->seed(...$seeded);

        $tester = $this->createLiveComponent(BatchedSelectionLineTable::class, ['lineClass' => $seeded[0]::class])
            ->selectTableRecords([...$seeded, 'garbage', '9~9'])
            ->callAction(TestAction::make('read')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['read 501 records, 501 distinct'])
            ->and(array_values(array_map(static fn (string $sql): int => mb_substr_count($sql, '?'), preg_grep('/ WHERE /', $this->statementsRun()))))->toBe([1000, 4]);

        $tester->callAction(TestAction::make('count')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['queried 501 rows'])
            ->and(array_values(array_map(static fn (string $sql): int => mb_substr_count($sql, '?'), preg_grep('/ WHERE /', $this->statementsRun()))))->toBe([1004]);

        $this->createLiveComponent(BatchedSelectionLineTable::class, ['lineClass' => $seeded[0]::class])
            ->selectTableRecords(array_slice($seeded, 0, 2))
            ->callAction(TestAction::make('read')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['read 2 records, 2 distinct'])
            ->and(array_values(array_map(static fn (string $sql): int => mb_substr_count($sql, '?'), preg_grep('/ WHERE /', $this->statementsRun()))))->toBe([4]);
    })->with('composite lines');

    it('subtracts a tracked deselection larger than one batch from every key the table admits, and keeps a smaller one one exclusion', function (Closure $lines): void {
        $seeded = $lines(502);
        $this->seed(...$seeded);
        $tracked = fn (array $deselected): Testable => $this->createLiveComponent(BatchedSelectionLineTable::class, ['lineClass' => $seeded[0]::class])
            ->set('isTrackingDeselectedTableRecords', true)
            ->set('deselectedTableRecords', array_map(fn (object $line): string => $this->createLiveComponent(BatchedSelectionLineTable::class, ['lineClass' => $seeded[0]::class])->component()->getTableRecordKey($line), $deselected));

        $tracked(array_slice($seeded, 0, 501))->callAction(TestAction::make('readKeys')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['read 1 keys, 1 distinct'])
            ->and(array_values(array_map(static fn (string $sql): int => mb_substr_count($sql, '?'), preg_grep('/ WHERE /', $this->statementsRun()))))->toBe([2]);

        $tracked(array_slice($seeded, 0, 1))->callAction(TestAction::make('readKeys')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['read 501 keys, 501 distinct'])
            ->and(array_values(array_map(static fn (string $sql): int => mb_substr_count($sql, '?'), preg_grep('/ WHERE /', $this->statementsRun()))))->toBe([2]);
    })->with('composite lines');

    it('hands a key-reading action only the keys the callback lets the user select, read batch by batch as records', function (Closure $lines): void {
        $seeded = $lines(501);
        $this->seed(...$seeded);

        $this->createLiveComponent(BatchedSelectionLineTable::class, ['lineClass' => $seeded[0]::class, 'refuses' => true])
            ->selectTableRecords($seeded)
            ->callAction(TestAction::make('readKeys')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['read 500 keys, 500 distinct'])
            ->and(array_values(array_map(static fn (string $sql): int => mb_substr_count($sql, '?'), preg_grep('/ WHERE /', $this->statementsRun()))))->toBe([1000, 2]);
    })->with('composite lines');

    it('reads two spellings of one key that land in different batches as one record, and as one key, and excludes a row by another spelling within one batch but not beyond it', function (): void {
        $this->seed(...$bands = array_map(static fn (int $rate): TariffBand => new TariffBand("{$rate}.50", 'z', "Band {$rate}"), range(1, 500)));

        $tester = $this->createLiveComponent(BatchedSelectionLineTable::class, ['lineClass' => TariffBand::class])
            ->selectTableRecords([...$bands, '1=2E5~z'])
            ->callAction(TestAction::make('read')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['read 500 records, 500 distinct'])
            ->and(array_values(array_map(static fn (string $sql): int => mb_substr_count($sql, '?'), preg_grep('/ WHERE /', $this->statementsRun()))))->toBe([1000, 2]);

        $tester->callAction(TestAction::make('readKeys')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['read 500 keys, 500 distinct']);

        $tester->set('isTrackingDeselectedTableRecords', true)->set('deselectedTableRecords', ['1=2E500~z'])->callAction(TestAction::make('readKeys')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['read 499 keys, 499 distinct']);

        $tester->set('deselectedTableRecords', ['1=2E500~z', ...array_map(static fn (int $rate): string => "{$rate}~y", range(1, 500))])->callAction(TestAction::make('readKeys')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['read 500 keys, 500 distinct']);
    });

    it('keeps a selection over one identifier field one statement past a batch', function (): void {
        $this->seed(...$products = array_map(static fn (int $index): Product => new Product("Product {$index}"), range(1, 501)));

        $this->createLiveComponent(ConfiguredSelectionProductTable::class)
            ->selectTableRecords($products)
            ->callAction(TestAction::make('archiveByKey')->table()->bulk());

        expect(array_values(array_map(static fn (string $sql): int => mb_substr_count($sql, '?'), preg_grep('/ WHERE /', $this->statementsRun()))))->toBe([501]);
    });
});

dataset('composite lines', [
    'scalar fields' => [static fn (int $count): array => array_map(static fn (int $line): OrderLine => new OrderLine(order: 1, line: $line), range(1, $count))],
    'an association plus a scalar' => [static function (int $count): array {
        $invoice = new Invoice(1);

        return array_map(static fn (int $line): InvoiceLine => new InvoiceLine($invoice, line: $line), range(1, $count));
    }],
]);
