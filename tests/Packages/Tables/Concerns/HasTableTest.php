<?php

declare(strict_types=1);

use Doctrine\ORM\PersistentCollection;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\FilteredLarderJarTable;
use Sylament\Tests\Fixtures\Tables\InjectingRecordsTable;
use Sylament\Tests\Fixtures\Tables\InvoiceLineTable;
use Sylament\Tests\Fixtures\Tables\JarredLarderTable;
use Sylament\Tests\Fixtures\Tables\KeyOverridingProductTable;
use Sylament\Tests\Fixtures\Tables\OrderLineTable;
use Sylament\Tests\Fixtures\Tables\ProductTable;
use Sylament\Tests\Fixtures\Tables\RememberingProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\ShapedRecordsTable;
use Sylament\Tests\Fixtures\Tables\TablePageController;
use Sylament\Tests\Fixtures\Tables\WalkerHintedOrderLineTable;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Error\RuntimeError;

describe('the table', function (): void {
    it('builds its table once in a request, however many reads ask for it', function (): void {
        $this->createLiveComponent(InjectingRecordsTable::class)->callAction(TestAction::make('inspect')->table('lamp'));

        expect(array_values(array_filter($this::getContainer()->get(HookLog::class)->hooks, static fn (string $hook): bool => !str_starts_with($hook, '{'))))
            ->toBe(['table built', 'inspected']);
    });

    it('stamps the rows of two hosts with two host ids', function (): void {
        $this->seed($lamp = new Product('Lamp'));

        $first = $this->createLiveComponent(ProductTable::class);
        $second = $this->createLiveComponent(ProductTable::class);
        $firstId = $first->component()->getTableHostId();
        $secondId = $second->component()->getTableHostId();

        expect($firstId)->toMatch('/^[0-9a-f]{8}$/')
            ->and($secondId)->toMatch('/^[0-9a-f]{8}$/')
            ->and($firstId)->not->toBe($secondId)
            ->and($first->render()->crawler()->filter(Selectors::row((string) $lamp->id))->attr('id'))->toBe("{$firstId}.table.records.{$lamp->id}")
            ->and($second->render()->crawler()->filter(Selectors::row((string) $lamp->id))->attr('id'))->toBe("{$secondId}.table.records.{$lamp->id}");
    });
});

describe('records', function (): void {
    it('reads its records once for a render, keyed by record key, and fills an aggregate column with one batched read', function (): void {
        $cellar = new Larder('Cellar');
        $annex = new Larder('Annex');

        foreach (['Apricot', 'Bramble', 'Cherry'] as $label) {
            $cellar->addJar(new LarderJar($label));
        }

        $annex->addJar(new LarderJar('Damson'));
        $this->seed($cellar, $annex);

        $crawler = $this->createLiveComponent(JarredLarderTable::class)->render()->crawler();

        expect($crawler->filter(Selectors::cell((string) $annex->id, 'jars_count'))->text())->toBe('1')
            ->and($crawler->filter(Selectors::cell((string) $cellar->id, 'jars_count'))->text())->toBe('3')
            ->and($this->statementsRun())->toHaveCount(3);
    });

    it('reads an aggregate column only while its cells render, none while only the groups of a grouping show', function (array $props, ?string $count, int $batches): void {
        $cellar = new Larder('Cellar');
        $cellar->addJar(new LarderJar('Apricot'));
        $cellar->addJar(new LarderJar('Bramble'));
        $this->seed($cellar);

        $crawler = $this->createLiveComponent(JarredLarderTable::class, $props)->render()->crawler();
        $cell = $crawler->filter(Selectors::cell((string) $cellar->id, 'jars_count'));

        expect($cell->count() === 0 ? null : $cell->text())->toBe($count)
            ->and(array_filter($this->statementsRun(), static fn (string $statement): bool => str_contains($statement, 'GROUP BY')))->toHaveCount($batches);
    })->with([
        'grouped, only the groups showing' => [['groupsOnly' => true, 'tableGrouping' => 'name'], null, 0],
        'groups only, nothing grouped' => [['groupsOnly' => true], '2', 1],
    ]);

    it('renders a pagination footer exactly when its records are a page', function (array $products, string $host, array $props, ?string $overview): void {
        $this->seed(...$products);

        $footer = $this->createLiveComponent($host, $props)->render()->crawler()->filter(SupportSelectors::PAGINATION);

        expect($footer)->toHaveCount($overview === null ? 0 : 1);

        if ($overview !== null) {
            expect($footer->text())->toContain($overview);
        }
    })->with([
        'a paginated query' => fn (): array => [array_map(static fn (int $number): Product => new Product("P{$number}"), range(10, 21)), ProductTable::class, [], 'Showing 1 to 10 of 12 results'],
        'a page a records() closure returns' => fn (): array => [[], ShapedRecordsTable::class, ['shape' => 'page'], 'Showing 1 to 2 of 12 results'],
        'rows a records() closure returns on a paginated table' => fn (): array => [[], ShapedRecordsTable::class, ['shape' => 'arrays'], null],
    ]);

    it('keys an array row by its __key or else the key it arrived under, and an object row by its id, a uuid printed', function (): void {
        $arrays = $this->createLiveComponent(ShapedRecordsTable::class, ['shape' => 'arrays'])->render()->crawler();
        $objects = $this->createLiveComponent(ShapedRecordsTable::class, ['shape' => 'objects'])->render()->crawler();
        $page = $this->createLiveComponent(ShapedRecordsTable::class, ['shape' => 'page'])->render()->crawler();

        expect($arrays->filter(Selectors::row('lamp'))->text())->toBe('Lamp')
            ->and($arrays->filter(Selectors::row('vase'))->text())->toBe('Vase')
            ->and($objects->filter(Selectors::row('7'))->text())->toBe('Lamp')
            ->and($objects->filter(Selectors::row(ShapedRecordsTable::UUID))->text())->toBe('Vase')
            ->and($page->filter(Selectors::row('vase'))->text())->toBe('Vase');
    });

    it('keeps the last of two rows that arrive under one key', function (): void {
        $crawler = $this->createLiveComponent(ShapedRecordsTable::class, ['shape' => 'generator'])->render()->crawler();

        expect($crawler->filter(Selectors::ROW)->each(static fn (Crawler $row): string => $row->text()))->toBe(['Lantern']);
    });

    it('refuses rows it cannot key, naming what it was handed', function (string $shape, string $message): void {
        expect(fn (): mixed => $this->createLiveComponent(ShapedRecordsTable::class, ['shape' => $shape])->render())
            ->toThrow(static function (RuntimeError $error) use ($message): void {
                expect($error->getPrevious())->toBeInstanceOf(LogicException::class)
                    ->and($error->getPrevious()?->getMessage())->toBe($message);
            });
    })->with([
        'an object row with no id' => ['idless', 'Object records must expose a readable non-blank [id] for identification; [Sylament\Tests\Fixtures\Tables\Record\ShelfItem] does not. Give it one, or use array records with a [__key] entry.'],
        'a string for rows' => ['text', 'The [records()] closure of table [Sylament\Tests\Fixtures\Tables\ShapedRecordsTable] must return an iterable or a [Sylament\Support\Pagination\Paginator], got string.'],
        'a row that is a number' => ['numberRow', 'Records of table [Sylament\Tests\Fixtures\Tables\ShapedRecordsTable] must be objects or field-keyed arrays, got int.'],
        'an array row with an integer key' => ['listRow', 'Array records of table [Sylament\Tests\Fixtures\Tables\ShapedRecordsTable] must be keyed by field name; one arrived carrying a non-string key.'],
        'a blank __key' => ['blankKey', 'Record arrays must have a unique, printable [__key] entry for identification.'],
    ]);

    it('lists and counts the rows of its records() closure without querying a query it also declares', function (): void {
        $this->seed(new Product('Desk'));

        $tester = $this->createLiveComponent(ShapedRecordsTable::class, ['shape' => 'besideQuery']);

        expect($tester->render()->crawler()->filter(Selectors::ROW)->each(static fn (Crawler $row): string => $row->text()))->toBe(['Lamp', 'Vase']);

        $tester->assertCountTableRecords(2);

        expect($this->statementsRun())->toBe([]);
    });

    it("hands its records() closure the host's browsing state by Filament's names, its table and host, and a public service by type but never a private one", function (): void {
        $this->createLiveComponent(InjectingRecordsTable::class)
            ->sortTable('name', 'desc')
            ->searchTable('am')
            ->searchTableColumns(['maker' => ['name' => ' Acme ']])
            ->filterTable('short')
            ->set('tableRecordsPerPage', '5');

        $injected = array_values(array_filter($this::getContainer()->get(HookLog::class)->hooks, static fn (string $hook): bool => str_starts_with($hook, '{')));

        expect(json_decode(end($injected), true, flags: JSON_THROW_ON_ERROR))->toBe([
            'columnSearches' => ['maker.name' => 'Acme'],
            'filters' => ['short' => ['isActive' => true]],
            'page' => 1,
            'recordsPerPage' => 5,
            'search' => 'am',
            'sort' => ['name', 'desc'],
            'sortColumn' => 'name',
            'sortDirection' => 'desc',
            'table' => 'its table',
            'liveComponent' => 'its host',
            'registry' => 'the shared instance',
            'privateLog' => 'none',
        ]);
    });
});

describe('record keys', function (): void {
    it('keys a composite row by every field and resolves its key back, a malformed key resolving nothing without a statement', function (object $line, object $other): void {
        $this->seed($line, $other);

        $tester = $this->createLiveComponent($line instanceof OrderLine ? OrderLineTable::class : InvoiceLineTable::class);

        expect($tester->render()->crawler()->filter(Selectors::cell('7~2', 'note'))->text())->toBe('Fragile');

        $host = $tester->component();
        $statements = count($this->statementsRun());
        $resolved = $host->getTableRecord('7~2');

        expect($resolved)->toBeInstanceOf($line::class)->toBeRecord(note: 'Fragile', line: 2)
            ->and($this->statementsRun())->toHaveCount($statements + 1)
            ->and($host->getTableRecord('7~x'))->toBeNull()
            ->and($host->getTableRecord('7'))->toBeNull()
            ->and($host->getTableRecord(null))->toBeNull()
            ->and($this->statementsRun())->toHaveCount($statements + 1);
    })->with([
        'scalar fields' => fn (): array => [new OrderLine(order: 7, line: 2, note: 'Fragile'), new OrderLine(order: 7, line: 3, note: 'Sturdy')],
        'an association plus a scalar' => fn (): array => [new InvoiceLine($seven = new Invoice(7), line: 2, note: 'Fragile'), new InvoiceLine($seven, line: 3, note: 'Sturdy')],
    ]);

    it('keys every row by the key its host overrides and resolves that key back', function (string $lane, string $prefix): void {
        $this->seed($lamp = new Product('Lamp'));

        $tester = $this->createLiveComponent(KeyOverridingProductTable::class, ['lane' => $lane, 'keyPrefix' => $prefix]);
        $crawler = $tester->render()->crawler();

        $tester->callAction(TestAction::make('inspect')->table($lamp));

        expect($crawler->filter(Selectors::row("{$prefix}{$lamp->id}"))->text())->toContain('Lamp')
            ->and($crawler->filter(Selectors::row("{$prefix}{$lamp->id}") . ' input[type="checkbox"]')->attr('value'))->toBe("{$prefix}{$lamp->id}")
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toContain('inspect Lamp');
    })->with([
        'rows a records() closure lists, under a key of its own' => ['records', 'sku-'],
        "rows a query lists, under the key the table reads back" => ['query', ''],
    ]);

    it('refuses on its first render a query whose rows its host keys otherwise than the table reads keys back', function (): void {
        $this->seed($lamp = new Product('Lamp'));

        expect(fn (): mixed => $this->createLiveComponent(KeyOverridingProductTable::class, ['keyPrefix' => 'sku-'])->render())
            ->toThrow(RuntimeError::class, sprintf('Table [%s] keys a [%s] row as [sku-%d] through getTableRecordKey(), but its query reads every key back through RecordKey, which keys that row as [%d]', KeyOverridingProductTable::class, Product::class, $lamp->id, $lamp->id));
    });

    it('keys the rows a query projects into another class by its host alone', function (): void {
        $this->seed($lamp = new Product('Lamp'));

        $crawler = $this->createLiveComponent(KeyOverridingProductTable::class, ['lane' => 'projection', 'keyPrefix' => 'sku-'])->render()->crawler();

        expect($crawler->filter(Selectors::row("sku-{$lamp->id}"))->text())->toContain('Lamp');
    });
});

describe('record resolution', function (): void {
    it("resolves a row action's record within the filters, whatever the search, the page or the sort", function (): void {
        $jars = array_map(static fn (int $number): LarderJar => new LarderJar(sprintf('J%02d', $number), seal: JarSeal::Wax), range(1, 11));
        $this->seed($unsealed = new LarderJar('J12'), ...$jars);

        $tester = $this->createLiveComponent(FilteredLarderJarTable::class)
            ->sortTable('label', 'desc')
            ->assertCanNotSeeTableRecords([$jars[0]])
            ->callAction(TestAction::make('inspect')->table($jars[0]));

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['inspect J01']);

        $tester->searchTable('J11')->callAction(TestAction::make('inspect')->table($jars[1]));

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['inspect J02']);

        $tester->call('mountAction', ['name' => 'inspect', 'context' => ['table' => true, 'recordKey' => (string) $unsealed->id]])
            ->assertActionNotMounted()
            ->assertCanSeeTableRecords([$jars[10]]);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);
    });

    it('reads a records() row fresh for an action without keeping it for the render, so the render after it lists the rows the action left', function (): void {
        $this->seed($lamp = new Product('Lamp'), $desk = new Product('Desk'));

        $tester = $this->createLiveComponent(KeyOverridingProductTable::class, ['lane' => 'records'])
            ->mountAction(TestAction::make('delete')->table($lamp));

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['records read', 'records read']);

        $tester->callMountedAction();

        expect($tester->render()->crawler()->filter(Selectors::columnCells('name'))->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['Desk'])
            ->and($tester->component()->getTableRecord((string) $desk->id))->toBeInstanceOf(Product::class)
            ->and($tester->component()->getTableRecord((string) $lamp->id))->toBeNull();
    });

    it('resolves a record whose query fetch-joins a collection with every item of it, in one statement', function (): void {
        $cellar = new Larder('Cellar');

        foreach (['Apricot', 'Bramble', 'Cherry'] as $label) {
            $cellar->addJar(new LarderJar($label));
        }

        $this->seed($cellar);

        $host = $this->createLiveComponent(JarredLarderTable::class, ['fetchesJars' => true])->component();
        $statements = count($this->statementsRun());
        $resolved = $host->getTableRecord((string) $cellar->id);

        expect($resolved)->toBeInstanceOf(Larder::class)
            ->and($resolved->jars)->toBeInstanceOf(PersistentCollection::class)
            ->and($resolved->jars->isInitialized())->toBeTrue()
            ->and($resolved->jars->map(static fn (LarderJar $jar): string => $jar->label)->toArray())->toBe(['Apricot', 'Bramble', 'Cherry'])
            ->and($this->statementsRun())->toHaveCount($statements + 1);
    });
});

describe('the query', function (): void {
    it('lists the filtered, then searched rows, the group order first and the sort breaking ties inside each group', function (): void {
        $this->seed(
            new LarderJar('Apricot', seal: JarSeal::Wax, position: 2),
            new LarderJar('Bramble', seal: JarSeal::Wax, position: 1),
            new LarderJar('Banana', seal: JarSeal::Screw, position: 2),
            new LarderJar('Almond', seal: JarSeal::Screw, position: 1),
            new LarderJar('Cherry', seal: JarSeal::Wax, position: 1),
            new LarderJar('Damson', position: 1),
        );

        $crawler = $this->createLiveComponent(FilteredLarderJarTable::class)
            ->searchTable('a')
            ->sortTable('label', 'desc')
            ->call('groupTable', ['group' => 'position'])
            ->render()
            ->crawler();

        expect($crawler->filter(Selectors::columnCells('label'))->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['Bramble', 'Almond', 'Banana', 'Apricot']);
    });

    it("runs every filter's base query before any filter's query, so a join one filter adds serves another declared before it", function (): void {
        $acme = new Product('Acme');
        $brine = new Product('Brine');
        $this->seed(
            $acme,
            $brine,
            new LarderJar('Apricot', seal: JarSeal::Wax, supplier: $acme),
            new LarderJar('Bramble', seal: JarSeal::Wax, supplier: $brine),
            new LarderJar('Cherry', supplier: $acme),
        );

        $crawler = $this->createLiveComponent(FilteredLarderJarTable::class, ['filtersByMaker' => true])
            ->filterTable('fromAcme')
            ->render()
            ->crawler();

        expect($crawler->filter(Selectors::columnCells('label'))->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['Apricot']);
    });

    it('fetch-joins the to-one a shown column reads on the page alone, never on the count or a resolved record', function (): void {
        $acme = new Product('Acme');
        $this->seed($acme, $jar = new LarderJar('Apricot', seal: JarSeal::Wax, supplier: $acme));

        $tester = $this->createLiveComponent(FilteredLarderJarTable::class);
        $rendered = $tester->render()->crawler();
        $page = $this->statementsRun();

        $tester->assertCountTableRecords(1);
        $resolved = $tester->component()->getTableRecord((string) $jar->id);
        $afterwards = array_slice($this->statementsRun(), count($page));

        expect($rendered->filter(Selectors::cell((string) $jar->id, 'supplier.name'))->text())->toBe('Acme')
            ->and(array_filter($page, static fn (string $statement): bool => str_contains($statement, 'LEFT JOIN')))->not->toBe([])
            ->and($resolved)->toBeInstanceOf(LarderJar::class)
            ->and($afterwards)->toHaveCount(2)
            ->and(array_filter($afterwards, static fn (string $statement): bool => str_contains($statement, 'JOIN')))->toBe([]);
    });

    it('joins nothing for a to-one column the developer hid, the user toggled off, or a table showing only its groups', function (array $props): void {
        $acme = new Product('Acme');
        $this->seed($acme, new LarderJar('Apricot', seal: JarSeal::Wax, supplier: $acme));

        $crawler = $this->createLiveComponent(FilteredLarderJarTable::class, $props)->render()->crawler();

        expect($this->statementsRun())->not->toBe([])
            ->and(array_filter($this->statementsRun(), static fn (string $statement): bool => str_contains($statement, 'JOIN')))->toBe([])
            ->and($crawler->filter(Selectors::TABLE))->toHaveCount(1);
    })->with([
        'hidden by the developer' => [['maker' => 'hidden']],
        'toggled off' => [['maker' => 'toggledOff']],
        'showing only its groups' => [['groupsOnly' => true]],
    ]);
});

describe('the all-records count', function (): void {
    it('answers from the page it has read, with no second count', function (): void {
        $this->seed(...array_map(static fn (int $number): Product => new Product("P{$number}"), range(10, 21)));

        $host = $this->createLiveComponent(ProductTable::class)->component();
        $host->getTableRecords();

        $statements = count($this->statementsRun());

        expect($host->getAllTableRecordsCount())->toBe(12)
            ->and($this->statementsRun())->toHaveCount($statements);
    });

    it('counts the filtered, searched, unpaged roots in one statement otherwise', function (array $records, string $host, array $props, int $count): void {
        $this->seed(...$records);

        $tester = $this->createLiveComponent($host, $props);
        $tester->render();

        $statements = count($this->statementsRun());

        $tester->assertCountTableRecords($count);

        expect($this->statementsRun())->toHaveCount($statements + 1);
    })->with([
        'a search and a filter' => fn (): array => [[new LarderJar('Apricot', seal: JarSeal::Wax), new LarderJar('Almond'), new LarderJar('Cherry', seal: JarSeal::Wax)], FilteredLarderJarTable::class, ['tableSearch' => 'a'], 1],
        'a fetch-joined collection' => function (): array {
            $cellar = new Larder('Cellar');
            $cellar->addJar(new LarderJar('Apricot'));
            $cellar->addJar(new LarderJar('Bramble'));

            return [[$cellar, new Larder('Annex')], JarredLarderTable::class, ['fetchesJars' => true], 2];
        },
        'an identity derived from an association' => fn (): array => [[new InvoiceLine($seven = new Invoice(7), line: 2), new InvoiceLine($seven, line: 3)], InvoiceLineTable::class, [], 2],
    ]);

    it('counts the rows of a records() closure, and refuses to count a page that carries no total', function (): void {
        $this->createLiveComponent(ShapedRecordsTable::class, ['shape' => 'arrays'])->assertCountTableRecords(2);
        $this->createLiveComponent(ShapedRecordsTable::class, ['shape' => 'page'])->assertCountTableRecords(12);

        expect(fn (): mixed => $this->createLiveComponent(ShapedRecordsTable::class, ['shape' => 'simplePage'])->assertCountTableRecords(1))
            ->toThrow(LogicException::class, 'Table [' . ShapedRecordsTable::class . '] cannot count its records: a simple-paginated records() closure carries no total.');
    });

    it('refuses by name to count a composite root whose query names its own output walker', function (): void {
        $this->seed(new OrderLine(order: 7, line: 2));

        $tester = $this->createLiveComponent(WalkerHintedOrderLineTable::class);

        expect($tester->render()->crawler()->filter(Selectors::row('7~2')))->toHaveCount(1)
            ->and(fn (): mixed => $tester->assertCountTableRecords(1))
            ->toThrow(LogicException::class, 'Cannot read table [' . WalkerHintedOrderLineTable::class . ']: the record class [' . OrderLine::class . '] is keyed by more than one field or by an association, and the table query sets its own output walker');
    });
});

describe('the URL', function (): void {
    it('seeds the sort, the search and the page its URL names, the seeded search keeping the seeded page', function (): void {
        $this->seed(new Product('A01'), ...array_map(static fn (int $number): Product => new Product(sprintf('P%02d', $number)), range(1, 12)));
        $names = static fn (Crawler $page): array => $page->filter(Selectors::columnCells('name'))->each(static fn (Crawler $cell): string => $cell->text());

        $bound = $this->visit(TablePageController::url('Tables:RememberingProductTable') . '?search=P&sort=name:desc&shelfPage=2');
        $unbound = $this->visit(TablePageController::url('Tables:ProductTable') . '?search=P&page=2');

        expect($names($bound))->toBe(['P02', 'P01'])
            ->and($names($unbound))->toHaveCount(10)
            ->and($names($unbound)[0])->toBe('A01');
    });

    it("prefixes only the page parameter, by its host's identifier and never its table's", function (): void {
        $this->seed(...array_map(static fn (int $number): Product => new Product(sprintf('P%02d', $number)), range(1, 12)));

        $page = $this->visit(TablePageController::url('Tables:RememberingProductTable') . '?ledgerPage=2&page=2');

        expect($page->filter(Selectors::columnCells('name'))->first()->text())->toBe('P01')
            ->and($this->createLiveComponent(RememberingProductTable::class)->component()->getIdentifiedTableQueryStringPropertyNameFor('page'))->toBe('ledgerPage');
    });

    it('writes the state it binds into the URL a live request answers with, and nothing for a host that binds none', function (): void {
        $this->seed(new Product('Lamp'));
        $client = $this::getContainer()->get('test.client');
        $client->setServerParameter('HTTP_X_LIVE_URL', '/products?keep=1');

        $bound = $this->createLiveComponent(RememberingProductTable::class, client: $client);
        $first = $bound->response()->headers->get('X-Live-Url');
        $searched = $bound->searchTable('Lamp')->response()->headers->get('X-Live-Url');
        $unbound = $this->createLiveComponent(ProductTable::class, client: $client)->refresh()->response()->headers->get('X-Live-Url');

        expect($first)->toBe('/products?keep=1&shelfPage=1&search=')
            ->and($searched)->toBe('/products?keep=1&shelfPage=1&search=Lamp')
            ->and($unbound)->toBe('/products?keep=1');
    });
});
