<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Tables\GroupableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SummarizedJarTable;
use Sylament\Tests\Fixtures\Tables\SummarizedLarderTable;
use Sylament\Tests\Fixtures\Tables\SummarizedLineTable;
use Symfony\Component\DomCrawler\Crawler;

describe('the datasets', function (): void {
    it("sums the whole searched set on the all-table row and the page's records on the page row, which a search to one page drops", function (): void {
        $this->seed(...array_map(static fn (string $label, int $position): LarderJar => new LarderJar($label, position: $position), ['Apricot', 'Bramble', 'Apple', 'Cherry', 'Avocado'], [1, 2, 3, 4, 5]));

        $tester = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['positionOnly']]);
        $figures = fn (string $scope): array => $this::textSummaries($tester->render()->crawler()->filter(Selectors::summaryRow($scope)));

        expect($figures('page'))->toBe(['Sum 3'])
            ->and($figures('all'))->toBe(['Sum 15']);

        $tester->searchTable('e');

        expect($figures('page'))->toBe(['Sum 5'])
            ->and($figures('all'))->toBe(['Sum 9']);

        $tester->searchTable('Ap');

        expect($figures('page'))->toBe([])
            ->and($figures('all'))->toBe(['Sum 4']);
    });

    it('renders no page row on a table that lists every record at once, summing them all on the all-table row', function (): void {
        $this->seed(new LarderJar('Apricot', position: 1), new LarderJar('Bramble', position: 2), new LarderJar('Cherry', position: 4));

        $crawler = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['positionOnly', 'unpaginated']])->render()->crawler();

        expect($crawler->filter(Selectors::ROW))->toHaveCount(3)
            ->and($crawler->filter(Selectors::summaryRow('page')))->toHaveCount(0)
            ->and($this::textSummaries($crawler->filter(Selectors::summaryRow('all'))))->toBe(['Sum 7']);
    });

    it("narrows the page row to the page's keys, bound as the identifier's own type, and not at all where the page is the whole filtered set", function (): void {
        $this->seed($apricot = new LarderJar('Apricot', position: 1), $bramble = new LarderJar('Bramble', position: 2), new LarderJar('Cherry', position: 4));

        $tester = $this->createLiveComponent(SummarizedJarTable::class);
        $page = $tester->component()->getPageTableSummaryQuery();
        $whole = $tester->searchTable('Cherry')->component()->getPageTableSummaryQuery();

        expect($page?->getParameter('sylament_summary_keys')?->getValue())->toBe([$apricot->id, $bramble->id])
            ->and($whole)->toBeInstanceOf(QueryBuilder::class)
            ->and($whole?->getParameter('sylament_summary_keys'))->toBeNull();
    });

    it('narrows the page row of a composite-keyed table to the records on the page', function (string $lineClass, Closure $lines): void {
        $this->seed(...$lines());

        $crawler = $this->createLiveComponent(SummarizedLineTable::class, ['lineClass' => $lineClass])->render()->crawler();

        expect($this::textSummaries($crawler->filter(Selectors::summaryRow('page'))))->toBe(['Sum 3'])
            ->and($this::textSummaries($crawler->filter(Selectors::summaryRow('all'))))->toBe(['Sum 6']);
    })->with([
        'scalar fields' => [OrderLine::class, fn (): array => [new OrderLine(7, 1), new OrderLine(7, 2), new OrderLine(8, 3)]],
        'an association and a scalar' => [InvoiceLine::class, function (): array {
            $invoice = new Invoice(7);

            return [new InvoiceLine($invoice, 1), new InvoiceLine($invoice, 2), new InvoiceLine(new Invoice(8), 3)];
        }],
    ]);
});

describe('the batch', function (): void {
    it("reads every batchable figure of a row in one statement, a to-one path's included, and lets each range and list read its own", function (): void {
        $this->seed(new LarderJar('Apricot', new DateTimeImmutable('2024-03-05 10:00:00'), JarSeal::Wax, position: 1), new LarderJar('Bramble', position: 2), new LarderJar('Cherry', position: 4));

        $this->createLiveComponent(SummarizedJarTable::class)->render();
        $statements = $this->statementsRun();
        $batches = array_values(preg_grep('/\bSUM\(/', $statements));

        expect($batches)->toHaveCount(2)
            ->each->toMatch('/^SELECT SUM\(a0_\.position\) AS \w+, COUNT\(a0_\.sealedOn\) AS \w+, COUNT\(a1_\.name\) AS \w+ FROM LarderJar a0_ LEFT JOIN Larder a1_ /')
            ->and(preg_grep('/\bMIN\(a0_\.sealedOn\)/', $statements))->toHaveCount(2)
            ->and(preg_grep('/\bDISTINCT a0_\.seal\b/', $statements))->toHaveCount(2);
    });

    it('leaves a scoped summarizer out of the batch, reading its figure over its own scope', function (): void {
        $this->seed(new LarderJar('Apricot', position: 1), new LarderJar('Bramble', position: 2), new LarderJar('Cherry', position: 4));

        $crawler = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['positionOnly', 'scoped']])->render()->crawler();
        $statements = $this->statementsRun();

        expect($this::textSummaries($crawler->filter(Selectors::summaryRow('page'))))->toBe(['Sum 3', 'Past 1 2'])
            ->and($this::textSummaries($crawler->filter(Selectors::summaryRow('all'))))->toBe(['Sum 7', 'Past 1 6'])
            ->and(preg_grep('/\bSUM\(/', $statements))->toHaveCount(4)
            ->and(preg_grep('/\bSUM\(.*position > 1/', $statements))->toHaveCount(2);
    });

    it('keeps every figure on its own summarizer while a query-dependent hidden() shows a summarizer on one row and not on another', function (): void {
        $this->seed(
            new LarderJar('Apricot', new DateTimeImmutable('2024-03-05 10:00:00'), position: 1),
            new LarderJar('Bramble', position: 2),
            new LarderJar('Cherry', new DateTimeImmutable('2024-03-07 09:30:00'), position: 4),
        );

        $crawler = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['predicateHidden']])->render()->crawler();

        expect($this::textSummaries($crawler->filter(Selectors::summaryRow('page'))))->toBe(['Counted 2', 'Sum 3', 'Count 1', 'Count 0'])
            ->and($this::textSummaries($crawler->filter(Selectors::summaryRow('all'))))->toBe(['Sum 7', 'Count 2', 'Count 0']);
    });
});

describe('the group rows', function (): void {
    it("closes each group after its last record with a row over that group's records, one statement a group", function (): void {
        $this->seed(new LarderJar('Apricot', seal: JarSeal::Wax, position: 1), new LarderJar('Bramble', seal: JarSeal::Screw, position: 2));

        $crawler = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['positionOnly'], 'tableGrouping' => 'seal'])->render()->crawler();
        $kinds = $crawler->filter(Selectors::BODY_ROW)->each(fn (Crawler $row): string => match (true) {
            $row->matches(Selectors::GROUP_HEADER_ROW) => 'heading ' . $row->attr('data-sy-group'),
            $row->matches(Selectors::ROW) => 'record',
            $row->matches(Selectors::GROUP_SUMMARY_ROW) => implode(', ', $this::textSummaries($row)),
            default => $row->filter('th')->text(''),
        });

        expect($kinds)->toBe(['heading screw', 'record', 'Sum 2', 'heading wax', 'record', 'Sum 1', 'Summary'])
            ->and(preg_grep('/\bSUM\(/', $this->statementsRun()))->toHaveCount(3);
    });

    it('closes the group a page ends only once the next page starts another, reading the one record past the page to tell', function (array $seals, array $closed): void {
        $this->seed(...array_map(static fn (JarSeal $seal, int $position): LarderJar => new LarderJar(seal: $seal, position: $position), $seals, [1, 2, 4]));

        $crawler = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['positionOnly'], 'tableGrouping' => 'seal'])->render()->crawler();

        expect($this::textSummaries($crawler->filter(Selectors::GROUP_SUMMARY_ROW)))->toBe($closed)
            ->and(preg_grep('/\bLIMIT 1 OFFSET 2\b/', $this->statementsRun()))->toHaveCount(1);
    })->with([
        'the next page carrying the group on' => [[JarSeal::Wax, JarSeal::Wax, JarSeal::Wax], []],
        'the next page starting another' => [[JarSeal::Screw, JarSeal::Screw, JarSeal::Wax], ['Sum 3']],
    ]);

    it("closes the last page's group over every record of it, reading nothing past the page", function (): void {
        $this->seed(new LarderJar(seal: JarSeal::Wax, position: 1), new LarderJar(seal: JarSeal::Wax, position: 2), new LarderJar(seal: JarSeal::Wax, position: 4));

        $crawler = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['positionOnly'], 'tableGrouping' => 'seal', 'tablePage' => 2])->render()->crawler();

        expect($this::textSummaries($crawler->filter(Selectors::GROUP_SUMMARY_ROW)))->toBe(['Sum 7'])
            ->and(preg_grep('/\bLIMIT 1 OFFSET\b/', $this->statementsRun()))->toBe([]);
    });

    it('reads nothing past the page of a grouped table nothing summarizes', function (): void {
        $this->seed(new LarderJar(seal: JarSeal::Wax, position: 1), new LarderJar(seal: JarSeal::Wax, position: 2), new LarderJar(seal: JarSeal::Wax, position: 4));

        $crawler = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['unsummarized'], 'tableGrouping' => 'seal'])->render()->crawler();

        expect($crawler->filter(Selectors::GROUP_HEADER_ROW))->toHaveCount(1)
            ->and($crawler->filter(Selectors::ROW))->toHaveCount(2)
            ->and($crawler->filter(Selectors::GROUP_SUMMARY_ROW))->toHaveCount(0)
            ->and(preg_grep('/\bLIMIT 1 OFFSET\b/', $this->statementsRun()))->toBe([]);
    });

    it("reads past a page break for the key a closure gives a group, not for the column's value", function (): void {
        $this->seed(new LarderJar('Apple'), new LarderJar('Apricot'), new LarderJar('Avocado'));

        $crawler = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['closureGroups', 'summarized', 'paged'], 'tableGrouping' => 'initial'])->render()->crawler();

        expect($crawler->filter(Selectors::GROUP_HEADER_ROW))->toHaveCount(1)
            ->and($crawler->filter(Selectors::GROUP_SUMMARY_ROW))->toHaveCount(0);
    });

    it('reads past a page break by the record, not by the row, over a to-many fetch join', function (): void {
        $attic = new Larder('Attic');
        $attic->addJar(new LarderJar('Apricot'));
        $attic->addJar(new LarderJar('Bramble'));

        $atticToo = new Larder('Attic');
        $atticToo->addJar(new LarderJar('Cherry'));

        $cellar = new Larder('Cellar');
        $cellar->addJar(new LarderJar('Damson'));
        $this->seed($attic, $atticToo, $cellar);

        $page = fn (int $page): Crawler => $this->createLiveComponent(SummarizedLarderTable::class, ['variants' => ['joined', 'handOnly'], 'tableGrouping' => 'name', 'tablePage' => $page])->render()->crawler();

        expect($page(1)->filter(Selectors::GROUP_SUMMARY_ROW))->toHaveCount(0)
            ->and($page(2)->filter(Selectors::groupSummaryRow('Attic')))->toHaveCount(1);
    });
});
