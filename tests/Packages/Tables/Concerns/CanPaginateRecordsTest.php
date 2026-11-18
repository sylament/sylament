<?php

declare(strict_types=1);

use Doctrine\ORM\Query\SqlOutputWalker;
use Sylament\Tables\Testing\Testable;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\CachedLantern;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Greenhouse;
use Sylament\Tests\Fixtures\Entity\GreenhouseVent;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\LanternStand;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\KeyShapedPagedTable;
use Sylament\Tests\Fixtures\Tables\PagedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\RecordsPerPageReadingTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\TablePageController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Uid\Uuid;
use Twig\Error\RuntimeError;

describe('the page', function (): void {
    it('moves to the page a footer button names, keeping the page size chosen, and to the first page from one at or below zero', function (): void {
        $this->seed(...array_map(static fn (int $number): LarderJar => new LarderJar(sprintf('Jar %02d', $number)), range(1, 11)));
        $rows = static fn (Crawler $crawler): array => $crawler->filter(Selectors::ROW)->each(static fn (Crawler $row): string => $row->text());

        $tester = $this->createLiveComponent(PagedLarderJarTable::class, ['options' => [2, 5, 'all']])->call('gotoTablePage', ['page' => 2]);
        $crawler = $tester->render()->crawler();

        expect($rows($crawler))->toBe(['Jar 03', 'Jar 04'])
            ->and($crawler->filter(SupportSelectors::PAGINATION_OVERVIEW)->text())->toBe('Showing 3 to 4 of 11 results')
            ->and($crawler->filter(SupportSelectors::CURRENT_PAGE_BUTTON . '[disabled]')->text())->toBe('2')
            ->and($crawler->filter(SupportSelectors::PREVIOUS_PAGE_BUTTON)->attr('data-live-page-param'))->toBe('1')
            ->and($crawler->filter(SupportSelectors::NEXT_PAGE_BUTTON)->attr('data-live-page-param'))->toBe('3')
            ->and(array_unique($crawler->filter(SupportSelectors::PAGINATION . ' button')->each(static fn (Crawler $button): ?string => $button->attr('data-live-action-param'))))->toBe(['gotoTablePage']);

        expect($rows($tester->set('tableRecordsPerPage', '5')->call('gotoTablePage', ['page' => 2])->render()->crawler()))->toBe(['Jar 06', 'Jar 07', 'Jar 08', 'Jar 09', 'Jar 10']);

        $crawler = $tester->call('gotoTablePage', ['page' => 0])->render()->crawler();

        expect($rows($crawler))->toBe(['Jar 01', 'Jar 02', 'Jar 03', 'Jar 04', 'Jar 05'])
            ->and($crawler->filter(SupportSelectors::CURRENT_PAGE_BUTTON)->text())->toBe('1');
    });

    it('floors a page its URL names at the first, and caps one too far to offset at the last page whose offset it can compute', function (): void {
        $this->seed(...array_map(static fn (int $number): LarderJar => new LarderJar(sprintf('Jar %02d', $number)), range(1, 11)));

        $floored = $this->visit(TablePageController::url('Tables:PagedLarderJarTable') . '?page=0');
        $capped = $this->visit(TablePageController::url('Tables:PagedLarderJarTable') . '?page=' . PHP_INT_MAX);

        expect($floored->filter(SupportSelectors::PAGINATION_OVERVIEW)->text())->toBe('Showing 1 to 10 of 11 results')
            ->and($floored->filter(SupportSelectors::CURRENT_PAGE_BUTTON)->text())->toBe('1')
            ->and($capped->filter(SupportSelectors::PAGINATION_OVERVIEW)->text())->toBe('Showing 0 results of 11')
            ->and($capped->filter(SupportSelectors::PREVIOUS_PAGE_BUTTON)->attr('data-live-page-param'))->toBe('922337203685477579');
    });
});

describe('the page size', function (): void {
    it('pages by the option its select sends from the first page, by every record for all, and by its default for a value it does not offer', function (): void {
        $this->seed(...array_map(static fn (int $number): LarderJar => new LarderJar(sprintf('Jar %02d', $number)), range(1, 11)));
        $rows = static fn (Testable $tester): array => $tester->render()->crawler()->filter(Selectors::ROW)->each(static fn (Crawler $row): string => $row->text());

        $tester = $this->createLiveComponent(PagedLarderJarTable::class, ['options' => [2, 5, 'all']])->call('gotoTablePage', ['page' => 3]);

        expect($rows($tester->set('tableRecordsPerPage', '5')))->toBe(['Jar 01', 'Jar 02', 'Jar 03', 'Jar 04', 'Jar 05'])
            ->and($rows($tester->set('tableRecordsPerPage', 'all')))->toHaveCount(11)
            ->and($rows($tester->set('tableRecordsPerPage', '7')))->toBe(['Jar 01', 'Jar 02'])
            ->and($rows($tester->set('tableRecordsPerPage', 'abc')))->toBe(['Jar 01', 'Jar 02']);
    });

    it('hands a records() closure only an option the table offers, whatever value the client forges', function (): void {
        $handed = fn (): ?string => array_last($this::getContainer()->get(HookLog::class)->hooks);

        $tester = $this->createLiveComponent(RecordsPerPageReadingTable::class);
        $tester->render();

        $unchosen = $handed();
        $tester->set('tableRecordsPerPage', '999');
        $forgedNumber = $handed();
        $tester->set('tableRecordsPerPage', 'abc');
        $forgedText = $handed();
        $this->createLiveComponent(RecordsPerPageReadingTable::class, ['tableRecordsPerPage' => '7'])->render();

        expect([$unchosen, $forgedNumber, $forgedText, $handed()])->toBe(['recordsPerPage 10', 'recordsPerPage 10', 'recordsPerPage 10', 'recordsPerPage 10']);
    });

    it('hands a records() closure the option chosen, all when all is, and null where the table has nothing to page by', function (array $variants, ?string $chosen, string $handed): void {
        $tester = $this->createLiveComponent(RecordsPerPageReadingTable::class, ['variants' => $variants]);

        $chosen === null ? $tester->render() : $tester->set('tableRecordsPerPage', $chosen);

        expect(array_last($this::getContainer()->get(HookLog::class)->hooks))->toBe("recordsPerPage {$handed}");
    })->with([
        'an option' => [[], '5', '5'],
        'all' => [[], 'all', "'all'"],
        'an unpaginated table' => [['unpaginated'], null, 'NULL'],
        'a table with no page options' => [['noOptions'], null, 'NULL'],
    ]);

    it('puts its page size on the wire as the string its select holds, and null with no select where there is nothing to page by, and keeps its page when that value comes back unchanged', function (): void {
        $this->seed(...array_map(static fn (int $number): LarderJar => new LarderJar(sprintf('Jar %02d', $number)), range(1, 11)));
        $wire = static fn (Testable $tester): array => json_decode($tester->render()->crawler()->filter(Selectors::LIVE_PROPS)->attr('data-live-props-value') ?? '', true, flags: JSON_THROW_ON_ERROR);

        $tester = $this->createLiveComponent(PagedLarderJarTable::class, ['options' => [2, 5, 'all']]);

        expect($wire($tester)['tableRecordsPerPage'])->toBe('2')
            ->and($tester->render()->crawler()->filter(SupportSelectors::PER_PAGE_SELECT))->toHaveCount(1)
            ->and($wire($tester->set('tableRecordsPerPage', 'all'))['tableRecordsPerPage'])->toBe('all');

        $tester->set('tableRecordsPerPage', '5')->call('gotoTablePage', ['page' => 2])->set('tableRecordsPerPage', '5');

        expect($wire($tester))->toMatchArray(['tableRecordsPerPage' => '5', 'tablePage' => 2])
            ->and($tester->render()->crawler()->filter(Selectors::ROW)->each(static fn (Crawler $row): string => $row->text()))->toBe(['Jar 06', 'Jar 07', 'Jar 08', 'Jar 09', 'Jar 10']);

        foreach ([['variants' => ['unpaginated']], ['options' => []]] as $props) {
            $unpaged = $this->createLiveComponent(PagedLarderJarTable::class, $props);

            expect($wire($unpaged)['tableRecordsPerPage'])->toBeNull()
                ->and($unpaged->render()->crawler()->filter(SupportSelectors::PER_PAGE_SELECT))->toHaveCount(0);
        }
    });
});

describe('the query lane', function (): void {
    it('pages a query in its order, a deeper page continuing it, through one windowed read and one count', function (): void {
        $jars = array_map(static fn (int $number): LarderJar => new LarderJar(sprintf('Jar %02d', $number)), range(11, 1));
        $this->seed(...$jars);

        $tester = $this->createLiveComponent(PagedLarderJarTable::class, ['options' => [2, 5, 'all']]);
        $tester->render();

        $firstPage = count($this->statementsRun());

        $tester->assertCanSeeTableRecords([$jars[10], $jars[9]], inOrder: true)->call('gotoTablePage', ['page' => 6]);

        expect($firstPage)->toBe(2)
            ->and($this->statementsRun())->toHaveCount(2)
            ->and($tester->render()->crawler()->filter(SupportSelectors::PAGINATION_OVERVIEW)->text())->toBe('Showing 11 to 11 of 11 results');

        $tester->assertCanSeeTableRecords([$jars[0]])->assertCanNotSeeTableRecords([$jars[1]]);
    });

    it('reads a page over a to-one join as a plain window, and one over a to-many join through its identifiers first so every collection arrives whole', function (string $root, int $statements, array $rows): void {
        $larders = [];

        foreach (['Annex', 'Buttery', 'Cellar'] as $name) {
            $larder = new Larder($name);
            $larder->addJar(new LarderJar("{$name} jam", position: 1));
            $larder->addJar(new LarderJar("{$name} honey", position: 2));
            $larder->addJar(new LarderJar("{$name} pickle", position: 3));
            $larders[] = $larder;
        }

        $this->seed(...$larders);

        $crawler = $this->createLiveComponent(KeyShapedPagedTable::class, ['root' => $root])->render()->crawler();

        expect($this->statementsRun())->toHaveCount($statements)
            ->and($crawler->filter(Selectors::ROW)->each(static fn (Crawler $row): string => $row->text()))->toBe($rows);
    })->with([
        'a to-one join' => ['suppliedLarder', 2, ['Annex', 'Buttery']],
        'a to-many join' => ['larder', 3, ['Annex Annex jam, Annex honey, Annex pickle', 'Buttery Buttery jam, Buttery honey, Buttery pickle']],
    ]);

    it('reads a true total in the default mode, keeping a way back from a page past the end, and probes one record past the page with no count in the simple mode, where all reads a known total', function (): void {
        $this->seed(...array_map(static fn (int $number): LarderJar => new LarderJar(sprintf('Jar %02d', $number)), range(1, 11)));
        $rows = static fn (Crawler $crawler): array => $crawler->filter(Selectors::ROW)->each(static fn (Crawler $row): string => $row->text());

        $crawler = $this->createLiveComponent(PagedLarderJarTable::class, ['options' => [2, 5, 'all']])->call('gotoTablePage', ['page' => 9])->render()->crawler();

        expect($rows($crawler))->toBe([])
            ->and($this->statementsRun())->toHaveCount(2)
            ->and($this->statementsRun()[1])->toContain('COUNT')
            ->and($crawler->filter(SupportSelectors::PAGINATION_OVERVIEW)->text())->toBe('Showing 0 results of 11')
            ->and($crawler->filter(SupportSelectors::PREVIOUS_PAGE_BUTTON)->attr('data-live-page-param'))->toBe('8');

        $simple = $this->createLiveComponent(PagedLarderJarTable::class, ['options' => [2, 5, 'all'], 'mode' => 'Simple']);
        $crawler = $simple->render()->crawler();

        expect($this->statementsRun())->toHaveCount(1)
            ->and($this->statementsRun()[0])->not->toContain('COUNT')
            ->and($rows($crawler))->toBe(['Jar 01', 'Jar 02'])
            ->and($crawler->filter(SupportSelectors::NEXT_PAGE_BUTTON)->attr('data-live-page-param'))->toBe('2');

        $crawler = $simple->call('gotoTablePage', ['page' => 6])->render()->crawler();

        expect($rows($crawler))->toBe(['Jar 11'])
            ->and($crawler->filter(SupportSelectors::PREVIOUS_PAGE_BUTTON))->toHaveCount(1)
            ->and($crawler->filter(SupportSelectors::NEXT_PAGE_BUTTON))->toHaveCount(0);

        $crawler = $simple->set('tableRecordsPerPage', 'all')->render()->crawler();

        expect($rows($crawler))->toHaveCount(11)
            ->and($this->statementsRun())->toHaveCount(1)
            ->and($crawler->filter(SupportSelectors::PAGINATION_OVERVIEW)->text())->toBe('Showing 1 to 11 of 11 results');
    });

    it('refuses cursor pagination by name where it reads the mode, a mode a closure answers included', function (array $variants): void {
        expect(fn (): mixed => $this->createLiveComponent(PagedLarderJarTable::class, ['mode' => 'Cursor', 'variants' => $variants])->render())
            ->toThrow(static function (RuntimeError $error): void {
                expect($error->getPrevious())->toBeInstanceOf(LogicException::class)
                    ->and($error->getPrevious()?->getMessage())->toBe('Table [' . PagedLarderJarTable::class . '] requests cursor pagination, which needs a keyset carrier the toolkit does not ship yet; use PaginationMode::Default or PaginationMode::Simple.');
            });
    })->with([
        'the mode' => [[]],
        'a closure answering it' => [['modeByClosure']],
    ]);

    it('pages a root keyed by more than one field or by an association over its collection whole and in its order, reading its identifiers before its records, beside the count', function (string $root, string $collection, array $records, array $rows, ?int $statements): void {
        $this->seed(...$records);

        $crawler = $this->createLiveComponent(KeyShapedPagedTable::class, ['root' => $root])->render()->crawler();

        if ($statements !== null) {
            expect($this->statementsRun())->toHaveCount($statements);
        }

        expect($crawler->filter(Selectors::ROW)->each(static function (Crawler $row) use ($collection): array {
            $items = explode(', ', $row->filter(Selectors::columnCells($collection))->text());
            sort($items);

            return [(string) preg_replace('/^.*\.records\./', '', (string) $row->attr('id')), $items];
        }))->toBe($rows)
            ->and($crawler->filter(SupportSelectors::PAGINATION_OVERVIEW)->text())->toBe('Showing 1 to 2 of 3 results');
    })->with([
        'two scalars' => function (): array {
            $records = [];

            foreach ([[2, 1], [1, 2], [1, 1]] as [$aisle, $bay]) {
                $records[] = $stand = new LanternStand($aisle, $bay);

                foreach (['amber', 'brass'] as $name) {
                    $records[] = $lantern = new CachedLantern("{$aisle}.{$bay} {$name}");
                    $lantern->stand = $stand;
                }
            }

            return ['stand', 'lanterns.name', $records, [['1~1', ['1.1 amber', '1.1 brass']], ['1~2', ['1.2 amber', '1.2 brass']]], 3];
        },
        'an association and a scalar' => function (): array {
            $records = [$kew = new Garden('Kew')];

            foreach ([3, 2, 1] as $number) {
                $records[] = $allotment = new Allotment($kew, $number);

                foreach (['sage', 'thyme'] as $name) {
                    $records[] = $seedling = new Seedling("{$number} {$name}");
                    $seedling->allotment = $allotment;
                }
            }

            return ['allotment', 'seedlings.name', $records, [['1~1', ['1 sage', '1 thyme']], ['1~2', ['2 sage', '2 thyme']]], 3];
        },
        'one association alone' => function (): array {
            $records = [];

            foreach (['3', '1', '2'] as $digit) {
                $records[] = $vent = new GreenhouseVent($records[] = new Greenhouse(Uuid::fromString("00000000-0000-4000-8000-00000000000{$digit}")));

                foreach (['fern', 'moss'] as $name) {
                    $records[] = $seedling = new Seedling("{$digit} {$name}");
                    $seedling->vent = $vent;
                }
            }

            return ['ventSeedlings', 'seedlings.name', $records, [
                ['00000000-0000-4000-8000-000000000001', ['1 fern', '1 moss']],
                ['00000000-0000-4000-8000-000000000002', ['2 fern', '2 moss']],
            ], null];
        },
    ]);

    it("refuses by name a query naming its own output walker where a root keyed by more than one field cannot be read without Doctrine's", function (array $props, string $recordClass): void {
        expect(fn (): mixed => $this->createLiveComponent(KeyShapedPagedTable::class, [...$props, 'walker' => true])->render())
            ->toThrow(static function (RuntimeError $error) use ($recordClass): void {
                expect($error->getPrevious())->toBeInstanceOf(LogicException::class)
                    ->and($error->getPrevious()?->getMessage())->toStartWith(sprintf('Cannot read table [%s]: the record class [%s] is keyed by more than one field or by an association, and the table query sets its own output walker [%s].', KeyShapedPagedTable::class, $recordClass, SqlOutputWalker::class));
            });
    })->with([
        'the window of two scalars joining a collection, in the simple mode' => [['root' => 'stand', 'mode' => 'Simple'], LanternStand::class],
        'the window of an association and a scalar joining a collection, in the simple mode' => [['root' => 'allotment', 'mode' => 'Simple'], Allotment::class],
        'the total of two scalars' => [['root' => 'orderLine'], OrderLine::class],
        'the total of an association and a scalar' => [['root' => 'invoiceLine'], InvoiceLine::class],
    ]);

    it('leaves a query naming its own output walker alone wherever Doctrine reads it without its own', function (array $props, array $records, array $rows): void {
        $this->seed(...$records);

        $crawler = $this->createLiveComponent(KeyShapedPagedTable::class, [...$props, 'walker' => true])->render()->crawler();

        expect($crawler->filter(Selectors::ROW)->each(static fn (Crawler $row): string => $row->text()))->toBe($rows);
    })->with([
        'a generated key' => fn (): array => [['root' => 'product'], [new Product('Lamp'), new Product('Vase'), new Product('Desk')], ['Desk', 'Lamp']],
        'two scalars joining nothing, a page in the simple mode' => fn (): array => [['root' => 'orderLine', 'mode' => 'Simple'], [new OrderLine(7, 1, 'Nails'), new OrderLine(7, 2, 'Glue'), new OrderLine(8, 1, 'Tape')], ['Glue', 'Nails']],
        'two scalars joining nothing, every record' => fn (): array => [['root' => 'orderLine', 'tableRecordsPerPage' => 'all'], [new OrderLine(7, 1, 'Nails'), new OrderLine(7, 2, 'Glue'), new OrderLine(8, 1, 'Tape')], ['Glue', 'Nails', 'Tape']],
        'an association and a scalar, every record' => fn (): array => [['root' => 'invoiceLine', 'tableRecordsPerPage' => 'all'], [new InvoiceLine($seven = new Invoice(7), 1, 'Nails'), new InvoiceLine($seven, 2, 'Glue')], ['Glue', 'Nails']],
        'one association alone' => fn (): array => [['root' => 'vent'], [
            $north = new Greenhouse(Uuid::fromString('00000000-0000-4000-8000-000000000001')),
            $south = new Greenhouse(Uuid::fromString('00000000-0000-4000-8000-000000000002')),
            new GreenhouseVent($north),
            new GreenhouseVent($south),
        ], ['Vent 00000000-0000-4000-8000-000000000001', 'Vent 00000000-0000-4000-8000-000000000002']],
    ]);
});
