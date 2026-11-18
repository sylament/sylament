<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SessionSortedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\SortableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\TablePageController;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;
use Symfony\Component\DomCrawler\Crawler;

it('sorts by a sortable column and by one the user toggled off, never by a hidden, unsortable or undeclared one', function (): void {
    $this->seed(
        $cherry = new LarderJar('Cherry', sealedOn: new DateTimeImmutable('2024-03-01'), position: 2),
        $apple = new LarderJar('Apple', position: 3),
        $banana = new LarderJar('Banana', sealedOn: new DateTimeImmutable('2024-01-01'), position: 1),
    );

    $tester = $this->createLiveComponent(SortableLarderJarTable::class)
        ->sortTable('id', 'desc')
        ->assertCanSeeTableRecords([$banana, $cherry, $apple], inOrder: true)
        ->sortTable('seal')
        ->assertCanSeeTableRecords([$banana, $cherry, $apple], inOrder: true)
        ->sortTable('shelf')
        ->assertCanSeeTableRecords([$banana, $cherry, $apple], inOrder: true)
        ->sortTable('label')
        ->assertCanSeeTableRecords([$apple, $banana, $cherry], inOrder: true)
        ->sortTable('sealedOn', 'desc');

    expect($tester->render()->crawler()->filter(Selectors::headerCell('sealed-on')))->toHaveCount(0);

    $tester->assertCanSeeTableRecords(match (TestPlatform::current()) {
        TestPlatform::PostgreSql => [$apple, $cherry, $banana],
        TestPlatform::Sqlite, TestPlatform::MySql => [$cherry, $banana, $apple],
    }, inOrder: true);
});

describe('the default sort', function (): void {
    it("breaks the ties of the user's sort in its own direction, then by the key in that direction", function (): void {
        $this->seed(
            $figLate = new LarderJar('Fig', position: 2),
            $figEarly = new LarderJar('Fig', position: 1),
            $plum = new LarderJar('Plum', position: 5),
            $figEarlyToo = new LarderJar('Fig', position: 1),
        );

        $this->createLiveComponent(SortableLarderJarTable::class)
            ->sortTable('label', 'desc')
            ->assertCanSeeTableRecords([$plum, $figEarly, $figEarlyToo, $figLate], inOrder: true);
    });

    it("breaks the ties of the user's sort by the key in the user's direction when the table declares none", function (): void {
        $this->seed($figFirst = new LarderJar('Fig', position: 1), $figSecond = new LarderJar('Fig', position: 2), $plum = new LarderJar('Plum'));

        $this->createLiveComponent(SortableLarderJarTable::class, ['variants' => ['noDefaultSort']])
            ->sortTable('label', 'desc')
            ->assertCanSeeTableRecords([$plum, $figSecond, $figFirst], inOrder: true);
    });

    it('orders by a path no column carries', function (): void {
        $this->seed(
            $zest = new Product('Zest'),
            $acorn = new Product('Acorn'),
            $zested = new LarderJar('Apple', supplier: $zest, position: 1),
            $acorned = new LarderJar('Pear', supplier: $acorn, position: 2),
        );

        $this->createLiveComponent(SortableLarderJarTable::class, ['variants' => ['pathDefaultSort']])
            ->assertCanSeeTableRecords([$acorned, $zested], inOrder: true);
    });

    it('reads a direction declared in capitals as the direction it names, the key included', function (): void {
        $this->seed($first = new LarderJar('Fig', position: 1), $second = new LarderJar('Fig', position: 2), $secondToo = new LarderJar('Fig', position: 2));

        $this->createLiveComponent(SortableLarderJarTable::class, ['variants' => ['upperCaseDescDefaultSort']])
            ->assertCanSeeTableRecords([$secondToo, $second, $first], inOrder: true);
    });

    it('orders last by the key unless the table turns its key sort off', function (array $variants, string $orderBy): void {
        $this->seed(new LarderJar('Fig'));

        $this->createLiveComponent(SortableLarderJarTable::class, ['variants' => $variants])->sortTable('label', 'desc');

        $page = array_values(array_filter($this->statementsRun(), static fn (string $sql): bool => str_starts_with($sql, 'SELECT a0_.id AS id_0')));

        expect($page)->toHaveCount(1)
            ->and($page[0])->toEndWith("{$orderBy} LIMIT 10");
    })->with([
        'key sort on' => [[], 'ORDER BY a0_.label DESC, a0_.position ASC, a0_.id ASC'],
        'key sort off' => [['noKeySort'], 'ORDER BY a0_.label DESC, a0_.position ASC'],
    ]);
});

it("lists a relation-scoped table in its association's declared order when it declares no default sort", function (): void {
    $pantry = new Larder('Pantry');
    $pantry->addJar($second = new LarderJar('Apple', position: 2));
    $pantry->addJar($third = new LarderJar('Banana', position: 3));
    $pantry->addJar($first = new LarderJar('Cherry', position: 1));
    $this->seed($pantry, $elsewhere = new LarderJar('Damson'));

    $this->createLiveComponent(SortableLarderJarTable::class, ['variants' => ['relationship', 'noDefaultSort']])
        ->assertCanSeeTableRecords([$first, $second, $third], inOrder: true)
        ->assertCanNotSeeTableRecords([$elsewhere]);
});

it('runs a table declaring a relationship beside its query on the query, never reading the relationship for an order', function (): void {
    $pantry = new Larder('Pantry');
    $pantry->addJar($late = new LarderJar('Apple', position: 2));
    $pantry->addJar($early = new LarderJar('Banana', position: 1));
    $this->seed($pantry, $elsewhere = new LarderJar('Cherry', position: 0));

    $this->createLiveComponent(SortableLarderJarTable::class, ['variants' => ['relationshipBesideQuery', 'noDefaultSort']])
        ->assertCanSeeTableRecords([$late, $early, $elsewhere], inOrder: true);

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);
});

it("remembers a table's sort on the next page load only when the table persists it", function (string $host, string $labelSort, bool $remembers): void {
    $this->seed($apple = new LarderJar('Apple', position: 1), $banana = new LarderJar('Banana', position: 2));
    $client = $this::getContainer()->get('test.client');

    $this->createLiveComponent($host, client: $client)->sortTable('label', 'desc');

    $page = $this->visit(TablePageController::url('Tables:' . new ReflectionClass($host)->getShortName()), client: $client);
    $keys = $page->filter(Selectors::ROW)->each(static fn (Crawler $row): string => mb_substr((string) $row->attr('id'), (int) mb_strrpos((string) $row->attr('id'), '.') + 1));

    expect($page)->toContainElement(Selectors::sortState('label', $labelSort))
        ->and($keys)->toBe(array_map(static fn (LarderJar $jar): string => (string) $jar->id, $remembers ? [$banana, $apple] : [$apple, $banana]));
})->with([
    'persisted' => [SessionSortedLarderJarTable::class, 'descending', true],
    'not persisted' => [SortableLarderJarTable::class, 'none', false],
]);
