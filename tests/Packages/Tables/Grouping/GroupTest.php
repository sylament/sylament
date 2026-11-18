<?php

declare(strict_types=1);

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Sylament\Tables\Grouping\Group;
use Sylament\Tests\Fixtures\Entity\DayStampedJar;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Forms\PlainSuit;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\GroupableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\GroupedArrayRowsTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\String\UnicodeString;

it('refuses a blank id by the class and an id carrying a colon by the id', function (): void {
    $blank = 'Group of class [Sylament\Tables\Grouping\Group] must have a unique id, passed to the [make()] method.';
    $colon = 'Group of class [Sylament\Tables\Grouping\Group] has the id [seal:desc], which must not contain a colon';

    expect(static fn (): Group => Group::make())->toThrow(LogicException::class, $blank)
        ->and(static fn (): Group => Group::make(''))->toThrow(LogicException::class, $blank)
        ->and(static fn (): Group => Group::make('seal:desc'))->toThrow(LogicException::class, $colon)
        ->and(static fn (): Group => Group::make('seal')->id('seal:desc'))->toThrow(LogicException::class, $colon);
});

it('labels a group as declared, else by the second-to-last segment of its id, spaced and title-cased', function (): void {
    $this->seed(new LarderJar('Apple', position: 3));

    $crawler = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['defaultInstance']])->render()->crawler();

    expect($crawler->filter('select[aria-label="Group by"]')->first()->filter('option')->each(static fn (Crawler $option): string => $option->text()))->toBe(['-', 'Seal', 'Sealed on', 'Supplier', 'Larder', 'Label'])
        ->and($crawler->filter(Selectors::GROUP_HEADER_ROW . ' .sy-ta-group-heading')->text())->toBe('Shelf position: 3');
});

it("puts a collapse button on a collapsible group's header, and drops the label from a heading not prefixed with it", function (string $grouping, string $heading, bool $collapsible): void {
    $this->seed($lamp = new Product('Lamp'), new LarderJar('Apple', seal: JarSeal::Wax, supplier: $lamp));

    $header = $this->createLiveComponent(GroupableLarderJarTable::class, ['tableGrouping' => $grouping])->render()->crawler()->filter(Selectors::GROUP_HEADER_ROW);

    expect($header->filter('.sy-ta-group-heading')->text())->toBe($heading)
        ->and($header->filter('.sy-ta-group-header.sy-collapsible'))->toHaveCount($collapsible ? 1 : 0)
        ->and($header->filter("button[aria-label=\"{$heading}\"][aria-expanded=\"true\"]"))->toHaveCount($collapsible ? 1 : 0);
})->with([
    'collapsible, prefixed' => ['seal', 'Seal: wax', true],
    'neither' => ['supplier.name', 'Lamp', false],
]);

describe('keys and titles', function (): void {
    it("heads a backed enum's group with its backing value, as it keys it", function (): void {
        $this->seed(new LarderJar('Apple', seal: JarSeal::Wax), new LarderJar('Fig', seal: JarSeal::Screw));

        $headers = $this->createLiveComponent(GroupableLarderJarTable::class, ['tableGrouping' => 'seal'])->render()->crawler()->filter(Selectors::GROUP_HEADER_ROW);

        expect($headers->each(static fn (Crawler $header): array => [$header->attr('data-sy-group'), $header->filter('.sy-ta-group-heading')->text()]))->toBe([
            ['screw', 'Seal: screw'],
            ['wax', 'Seal: wax'],
        ])
            ->and($headers->last()->attr('id'))->toEndWith('.table.groups.wax');
    });

    it('heads a group by the label an enum gives itself, keyed by its backing value', function (): void {
        $headers = $this->createLiveComponent(GroupedArrayRowsTable::class, ['tableGrouping' => 'mode'])->render()->crawler()->filter(Selectors::GROUP_HEADER_ROW);

        expect($headers->each(static fn (Crawler $header): string => $header->filter('.sy-ta-group-heading')->text()))->toBe(['Mode: Standard delivery', 'Mode: By courier'])
            ->and($headers->first()->attr('id'))->toEndWith('.table.groups.standard')
            ->and($headers->last()->attr('id'))->toEndWith('.table.groups.courier');
    });

    it('groups the records a key closure answers one value for under one heading, titled by the first of them', function (): void {
        $this->seed(new LarderJar('Apple'), new LarderJar('Apricot'), new LarderJar('Fig'));

        $crawler = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['closureGroups'], 'tableGrouping' => 'initial'])->render()->crawler();
        $headers = $crawler->filter(Selectors::GROUP_HEADER_ROW);

        expect($headers->each(static fn (Crawler $header): string => $header->filter('.sy-ta-group-heading')->text()))->toBe(['Initial: Apple', 'Initial: Fig'])
            ->and($headers->first()->attr('id'))->toEndWith('.table.groups.A')
            ->and($crawler->filter(Selectors::ROW)->each(static fn (Crawler $row): ?string => $row->attr('data-sy-group')))->toBe(['Apple', 'Apple', 'Fig'])
            ->and(array_values(array_unique($this::getContainer()->get(HookLog::class)->hooks)))->toBe(['key label']);
    });

    it("titles every record through a title closure, so one key's records titled apart open two headings", function (): void {
        $this->seed(new LarderJar('Apple'), new LarderJar('Apricot'), new LarderJar('Fig'));

        $headers = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['closureGroups'], 'tableGrouping' => 'titled'])->render()->crawler()->filter(Selectors::GROUP_HEADER_ROW);

        expect($headers->each(static fn (Crawler $header): string => $header->filter('.sy-ta-group-heading')->text()))->toBe(['Titled: Apple jar', 'Titled: Apricot jar', 'Titled: Fig jar'])
            ->and(array_count_values($this::getContainer()->get(HookLog::class)->hooks)['title'])->toBe(3);
    });

    it('keys a record by the value its column holds, and by none where the value cannot name a group', function (string $column, object|array $record, bool $date, ?string $key): void {
        expect(Group::make('value')->column($column)->date($date)->getStringKey($record))->toBe($key);
    })->with([
        'a string' => ['value', ['value' => 'Apple'], false, 'Apple'],
        'an integer' => ['value', ['value' => 3], false, '3'],
        'a backed enum' => ['value', ['value' => JarSeal::Wax], false, 'wax'],
        'a datetime' => ['value', ['value' => new DateTimeImmutable('2024-03-01 09:30:00')], false, '2024-03-01 09:30:00'],
        'a datetime under date()' => ['value', ['value' => new DateTimeImmutable('2024-03-01 09:30:00')], true, '2024-03-01'],
        'a string under date()' => ['value', ['value' => '2024-03-01'], true, null],
        'a stringable value' => ['value', ['value' => new UnicodeString('Top shelf')], false, 'Top shelf'],
        'a pure enum' => ['value', ['value' => PlainSuit::Hearts], false, null],
        'an array' => ['value', ['value' => ['Apple']], false, null],
        'a collection' => ['value', ['value' => new ArrayCollection(['Apple'])], false, null],
        'a blank string' => ['value', ['value' => ''], false, null],
        "an object's property path" => ['supplier.name', new LarderJar('Apple', supplier: new Product('Lamp')), false, 'Lamp'],
        'a path through a missing relation' => ['supplier.name', new LarderJar('Apple'), false, null],
        'a path the object does not expose' => ['nope', new LarderJar('Apple'), false, null],
    ]);

    it("groups a date() group by the day, headed in the table's default date format and ordered by the instant", function (): void {
        $this->seed(
            $apple = new LarderJar('Apple', sealedOn: new DateTimeImmutable('2024-03-01 18:00:00')),
            $fig = new LarderJar('Fig', sealedOn: new DateTimeImmutable('2024-03-01 09:00:00')),
            $cherry = new LarderJar('Cherry', sealedOn: new DateTimeImmutable('2024-03-02 00:00:00')),
            $damson = new LarderJar('Damson'),
        );

        $tester = $this->createLiveComponent(GroupableLarderJarTable::class, ['tableGrouping' => 'sealedOn']);
        $headers = $tester->render()->crawler()->filter(Selectors::GROUP_HEADER_ROW);
        $dated = [['Sealed on: Mar 1, 2024', '.table.groups.2024-03-01'], ['Sealed on: Mar 2, 2024', '.table.groups.2024-03-02']];
        $blank = ['Sealed on:', '.table.groups.'];

        expect($headers->each(static fn (Crawler $header): array => [$header->filter('.sy-ta-group-heading')->text(), mb_strstr((string) $header->attr('id'), '.')]))->toBe(match (TestPlatform::current()) {
            TestPlatform::Sqlite, TestPlatform::MySql => [$blank, ...$dated],
            TestPlatform::PostgreSql => [...$dated, $blank],
        });

        $tester->assertCanSeeTableRecords(match (TestPlatform::current()) {
            TestPlatform::Sqlite, TestPlatform::MySql => [$damson, $fig, $apple, $cherry],
            TestPlatform::PostgreSql => [$fig, $apple, $cherry, $damson],
        }, inOrder: true);
    });

    it('describes a group under its heading through a closure handed its title, a record, its column and the host', function (array $variants, ?string $description): void {
        $this->seed($lamp = new Product('Lamp'), new LarderJar('Apple', supplier: $lamp), new LarderJar('Fig', supplier: $lamp));

        $header = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => $variants, 'tableGrouping' => 'supplier.name'])->render()->crawler()->filter(Selectors::GROUP_HEADER_ROW);

        expect($header->filter('.sy-ta-group-heading')->text())->toBe('Lamp')
            ->and($header->filter('.sy-ta-group-description')->each(static fn (Crawler $paragraph): string => $paragraph->text()))->toBe($description === null ? [] : [$description]);
    })->with([
        'a description closure' => [['described'], 'Lamp by supplier.name, first Apple, grouped by supplier.name'],
        'none' => [[], null],
    ]);
});

describe('the order', function (): void {
    it('orders the rows by the group in the direction asked, joining a relation path, the rows without one where the platform puts a null', function (string $grouping): void {
        $this->seed(
            $lamp = new Product('Lamp'),
            $bowl = new Product('Bowl'),
            $apple = new LarderJar('Apple', supplier: $lamp),
            $fig = new LarderJar('Fig', supplier: $bowl),
            $cherry = new LarderJar('Cherry'),
        );

        $this->createLiveComponent(GroupableLarderJarTable::class, ['tableGrouping' => $grouping])->assertCanSeeTableRecords(match ([TestPlatform::current(), $grouping]) {
            [TestPlatform::Sqlite, 'supplier.name'], [TestPlatform::MySql, 'supplier.name'] => [$cherry, $fig, $apple],
            [TestPlatform::PostgreSql, 'supplier.name'] => [$fig, $apple, $cherry],
            [TestPlatform::Sqlite, 'supplier.name:desc'], [TestPlatform::MySql, 'supplier.name:desc'] => [$apple, $fig, $cherry],
            [TestPlatform::PostgreSql, 'supplier.name:desc'] => [$cherry, $apple, $fig],
        }, inOrder: true);
    })->with([
        'ascending' => ['supplier.name'],
        'descending' => ['supplier.name:desc'],
    ]);

    it('orders the rows through an order closure handed the column, the direction and the builder, by the builder it returns or the one it was handed', function (string $grouping): void {
        $this->seed($apple = new LarderJar('Apple', position: 1), $fig = new LarderJar('Fig', position: 3), $cherry = new LarderJar('Cherry', position: 2));

        $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['closureGroups'], 'tableGrouping' => $grouping])
            ->assertCanSeeTableRecords([$fig, $cherry, $apple], inOrder: true);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toContain('order position asc');
    })->with([
        'a returned builder' => ['reordered'],
        'the builder it was handed' => ['orderedInPlace'],
    ]);
});

describe('the scope', function (): void {
    it('narrows a query to one group inside what it already constrains, a blank key to the rows holding none, each parameter under a name of its own', function (): void {
        $this->seed(
            $lamp = new Product('Lamp'),
            new LarderJar('Apple', seal: JarSeal::Wax, supplier: $lamp, position: 2),
            new LarderJar('Fig', seal: JarSeal::Wax, position: 0),
            new LarderJar('Cherry', seal: JarSeal::Screw, supplier: $lamp, position: 2),
            new LarderJar('Damson', position: 2),
        );

        $jars = fn (): QueryBuilder => $this->entityManager()->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar')->andWhere('jar.position > :min')->setParameter('min', 1)->orderBy('jar.label');
        $labels = static fn (QueryBuilder $query): array => array_map(static fn (LarderJar $jar): string => $jar->label, $query->getQuery()->getResult());

        expect($labels(Group::make('seal')->scopeQueryByKey($jars(), 'wax')))->toBe(['Apple'])
            ->and($labels(Group::make('label')->scopeQueryByKey(Group::make('seal')->scopeQueryByKey($jars(), 'wax'), 'Apple')))->toBe(['Apple'])
            ->and($labels(Group::make('seal')->scopeQueryByKey($jars(), null)))->toBe(['Damson'])
            ->and($labels(Group::make('supplier.name')->scopeQueryByKey($jars(), null)))->toBe(['Damson']);
    });

    it('narrows a date() group to its half-open day, bound as the field it reads, and an unparsable key to nothing', function (): void {
        $this->seed(
            new LarderJar('Apple', sealedOn: new DateTimeImmutable('2024-03-01 23:59:59')),
            new LarderJar('Fig', sealedOn: new DateTimeImmutable('2024-03-02 00:00:00')),
            new LarderJar('Cherry', sealedOn: new DateTimeImmutable('2024-02-29 23:59:59')),
            new DayStampedJar('Plum', new DateTimeImmutable('2024-03-01')),
            new DayStampedJar('Quince', new DateTimeImmutable('2024-03-02')),
        );

        $labels = fn (string $class, string $key): array => array_map(
            static fn (LarderJar|DayStampedJar $jar): string => $jar->label,
            Group::make('sealedOn')->date()->scopeQueryByKey($this->entityManager()->createQueryBuilder()->select('jar')->from($class, 'jar'), $key)->getQuery()->getResult(),
        );

        expect($labels(LarderJar::class, '2024-03-01'))->toBe(['Apple'])
            ->and($labels(DayStampedJar::class, '2024-03-01'))->toBe(['Plum'])
            ->and($labels(LarderJar::class, 'March'))->toBe([]);
    });

    it('narrows through a scope closure handed what it names, by the builder it returns or the one it was handed, on either path', function (bool $returns): void {
        $this->seed($apple = new LarderJar('Apple'), $fig = new LarderJar('Fig'));

        $jars = fn (): QueryBuilder => $this->entityManager()->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar');
        $labels = static fn (QueryBuilder $query): array => array_map(static fn (LarderJar $jar): string => $jar->label, $query->getQuery()->getResult());
        $narrow = static function (QueryBuilder $query, string $column, string $value) use ($returns): ?QueryBuilder {
            $narrowed = $returns ? clone $query : $query;
            $narrowed->andWhere("jar.{$column} = :narrowed")->setParameter('narrowed', $value);

            return $returns ? $narrowed : null;
        };

        $byRecord = Group::make('label')->scopeQueryUsing(static fn (QueryBuilder $query, string $column, LarderJar $record): ?QueryBuilder => $narrow($query, $column, $record->label));
        $byKey = Group::make('label')->scopeQueryByKeyUsing(static fn (QueryBuilder $query, string $column, ?string $key): ?QueryBuilder => $narrow($query, $column, (string) $key));

        expect($labels($byRecord->scopeQuery($jars(), $apple)))->toBe(['Apple'])
            ->and($labels($byKey->scopeQueryByKey($jars(), 'Fig')))->toBe(['Fig'])
            ->and($labels($byKey->scopeQuery($jars(), $fig)))->toBe(['Fig']);
    })->with([
        'a returned builder' => [true],
        'the builder it was handed' => [false],
    ]);
});

it("answers every larder's heading on a page from the page's own query", function (): void {
    $this->seed(...array_map(static function (string $name): Larder {
        $larder = new Larder($name);
        $larder->addJar(new LarderJar("{$name} jar"));

        return $larder;
    }, ['Pantry', 'Cellar', 'Attic']));

    $headers = $this->createLiveComponent(GroupableLarderJarTable::class, ['tableGrouping' => 'larder.name'])->render()->crawler()->filter(Selectors::GROUP_HEADER_ROW);

    expect($headers->each(static fn (Crawler $header): string => $header->filter('.sy-ta-group-heading')->text()))->toBe(['Larder: Attic', 'Larder: Cellar', 'Larder: Pantry'])
        ->and(preg_grep('/FROM Larder\b/', $this->statementsRun()))->toBe([]);
});
