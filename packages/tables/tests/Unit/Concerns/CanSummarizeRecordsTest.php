<?php

declare(strict_types=1);

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountOutputWalker;
use Sylament\Support\Tests\Doctrine\DriverValues;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Sylament\Tables\Columns\Column;
use Sylament\Tables\Columns\Summarizers\Average;
use Sylament\Tables\Columns\Summarizers\Count;
use Sylament\Tables\Columns\Summarizers\Range;
use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tables\Columns\Summarizers\Values;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Grouping\Group;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Address;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Book;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\CompositePair;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Genre;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;

/**
 * Six songs summing to 210, in ascending play order across two artists — so a page
 * of the first two sums to 30 where the whole table sums to 210, and the two numbers
 * can never be mistaken for each other.
 */
function summaryStateSongs(?RecordingMiddleware $recorder = null): EntityManager
{
    $entityManager = EntityManagerFactory::create($recorder);

    $radiohead = new Artist(1, 'Radiohead', address: new Address('Abbey Road'));
    $bjork = new Artist(2, 'Björk', address: new Address('Laugavegur'));

    EntityManagerFactory::seed(
        $entityManager,
        $radiohead,
        $bjork,
        new Song(id: 1, title: 'Airbag', plays: 10, artist: $radiohead),
        new Song(id: 2, title: 'Let Down', plays: 20, artist: $radiohead),
        new Song(id: 3, title: 'No Surprises', plays: 30, artist: $radiohead),
        new Song(id: 4, title: 'Jóga', plays: 40, artist: $bjork),
        new Song(id: 5, title: 'Hyperballad', plays: 50, artist: $bjork),
        new Song(id: 6, title: 'Bachelorette', plays: 60, artist: $bjork),
    );

    return $entityManager;
}

/**
 * @param array<Column> $columns
 */
function summaryStateHost(EntityManager $entityManager, array $columns, int|string|null $recordsPerPage = 2, int $page = 1): TableHost
{
    $host = new TableHost(function (Table $table) use ($entityManager, $columns, $recordsPerPage): Table {
        $table = $table
            ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song')->orderBy('song.id', SortDirection::Ascending))
            ->columns($columns);

        return $recordsPerPage === null ? $table->paginated(false) : $table->paginated([$recordsPerPage]);
    });

    $host->tablePage = $page;

    return $host;
}

it('takes the all-table summary query from the filtered query, and reads it unsorted and unpaged', function (): void {
    $recorder = new RecordingMiddleware();
    $host = summaryStateHost(summaryStateSongs($recorder), [TextColumn::make('plays')->summarize(Sum::make())]);
    $query = $host->getAllTableSummaryQuery();

    $recorder->reset();

    expect($query)->toBeInstanceOf(QueryBuilder::class)
        ->and($query?->getMaxResults())->toBeNull()
        ->and(DriverValues::numeric($host->getTableSummarySelectedState($query)))->toBe(['sy_summary_0' => 210])
        ->and($recorder->statements())->toHaveCount(1)
        ->and($recorder->statements()[0])->not->toContain('ORDER BY')
        ->and($recorder->statements()[0])->not->toContain('LIMIT');
});

it('summarizes only what the filters and the searches left', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [TextColumn::make('title')->searchable(), TextColumn::make('plays')->summarize(Sum::make())]);
    $host->tableSearch = 'Airbag';

    expect(DriverValues::numeric($host->getTableSummarySelectedState($host->getAllTableSummaryQuery())))->toBe(['sy_summary_0' => 10]);
});

/*
 * The one case that catches the trap the whole design turns on: a LIMIT bounds an
 * aggregate's result row rather than the aggregated set, so a page summary written
 * with one reads the all-table figure on every page and never errors.
 */
it('summarizes the page over the page keys, to a different figure than the whole table', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [TextColumn::make('plays')->summarize(Sum::make())]);

    expect(DriverValues::numeric($host->getTableSummarySelectedState($host->getPageTableSummaryQuery())))->toBe(['sy_summary_0' => 30])
        ->and(DriverValues::numeric($host->getTableSummarySelectedState($host->getAllTableSummaryQuery())))->toBe(['sy_summary_0' => 210]);
});

it('narrows the page summary to the keys of the page being shown', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [TextColumn::make('plays')->summarize(Sum::make())], page: 2);
    $query = $host->getPageTableSummaryQuery();

    expect($query?->getDQL())->toContain('song.id IN (:sylament_summary_keys)')
        ->and($query?->getParameter('sylament_summary_keys')?->getValue())->toBe([3, 4])
        ->and($query?->getParameter('sylament_summary_keys')?->getType())->toBe(ArrayParameterType::INTEGER)
        ->and(DriverValues::numeric($host->getTableSummarySelectedState($query)))->toBe(['sy_summary_0' => 70]);
});

/*
 * One page holding everything makes the page query and the all-table query the
 * same set, so the narrowing reproduces what it started from at one bound
 * parameter per key per identifier field — and the figure it publishes is
 * identical either way. Both shapes of a single page are here: `'all'`, and a
 * page size the table never fills.
 */
it('leaves the page summary un-narrowed where one page holds the whole filtered set', function (int|string $recordsPerPage): void {
    $host = summaryStateHost(summaryStateSongs(), [TextColumn::make('plays')->summarize(Sum::make())], recordsPerPage: $recordsPerPage);
    $query = $host->getPageTableSummaryQuery();

    expect(DriverValues::numeric($host->getTableSummarySelectedState($query)))->toBe(['sy_summary_0' => 210])
        ->and($query?->getDQL())->not->toContain('sylament_summary_keys')
        ->and($query?->getParameters()->toArray())->toBe([]);
})->with(["recordsPerPage 'all'" => ['all'], 'a page size the table never fills' => [10]]);

/*
 * An empty page would bind an empty key list, and `IN ()` is a syntax error on most
 * platforms — the same guard the aggregate batch already carries. It is also the
 * ordering trap of the un-narrowed answer above: an empty table reports one page
 * too, so a guard placed over this one would publish a whole-table figure here.
 */
it('answers no page summary query for an empty page', function (int|string $recordsPerPage): void {
    $host = summaryStateHost(EntityManagerFactory::create(), [TextColumn::make('plays')->summarize(Sum::make())], recordsPerPage: $recordsPerPage);

    expect($host->getPageTableSummaryQuery())->toBeNull()
        ->and($host->getTableSummarySelectedState(null))->toBe([]);
})->with(['paginated([2])' => [2], "recordsPerPage 'all'" => ['all']]);

it('answers no page summary query for an unpaginated table', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [TextColumn::make('plays')->summarize(Sum::make())], recordsPerPage: null);

    expect($host->getPageTableSummaryQuery())->toBeNull()
        ->and($host->getAllTableSummaryQuery())->toBeInstanceOf(QueryBuilder::class);
});

it('names the record class own identifier in the page narrowing', function (): void {
    $entityManager = EntityManagerFactory::create();

    EntityManagerFactory::seed($entityManager, new Book('a', 'Dune'), new Book('b', 'Ubik'), new Book('c', 'Solaris'));

    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('book')->from(Book::class, 'book')->orderBy('book.code', SortDirection::Ascending))
        ->columns([TextColumn::make('title')->summarize(Count::make())])
        ->paginated([2]));

    expect($host->getPageTableSummaryQuery()?->getDQL())->toContain('book.code IN (:sylament_summary_keys)')
        ->and($host->getPageTableSummaryQuery()?->getParameter('sylament_summary_keys')?->getType())->toBe(ArrayParameterType::STRING)
        ->and(DriverValues::numeric($host->getTableSummarySelectedState($host->getPageTableSummaryQuery())))->toBe(['sy_summary_0' => 2]);
});

/**
 * Four residencies over two venues, positions 10/20/30/40 — so a page of the first
 * two sums to 30 where the whole table sums to 100, exactly as the songs above keep
 * a page figure and a table figure unmistakable. The venue half of each key is a
 * `Handle`, so the association component rides its target's own DBAL type.
 */
function summaryStateResidencies(?RecordingMiddleware $recorder = null): EntityManager
{
    $entityManager = EntityManagerFactory::create($recorder);

    $apollo = new Venue(new Handle('apollo'), 'Apollo');
    $forum = new Venue(new Handle('forum'), 'Forum');

    EntityManagerFactory::seed(
        $entityManager,
        $apollo,
        $forum,
        summaryStateResidency($apollo, '2026-spring', 'Apollo spring', 10),
        summaryStateResidency($apollo, '2026-autumn', 'Apollo autumn', 20),
        summaryStateResidency($forum, '2026-spring', 'Forum spring', 30),
        summaryStateResidency($forum, '2026-autumn', 'Forum autumn', 40),
    );

    return $entityManager;
}

function summaryStateResidency(Venue $venue, string $season, string $label, int $position): Residency
{
    $residency = new Residency();
    $residency->setVenue($venue);
    $residency->setSeason($season);
    $residency->setLabel($label);
    $residency->setPosition($position);

    return $residency;
}

/*
 * A composite key is a tuple rather than a column, so the page narrowing is one
 * OR-of-ANDs term per record with a bound parameter per identifier field — and the
 * all-table row, which keys nothing, keeps reading the figure it always did.
 */
it('narrows the page summary of a composite table by every identifier field', function (): void {
    $entityManager = EntityManagerFactory::create();

    EntityManagerFactory::seed($entityManager, new CompositePair(1, 1, 'first'), new CompositePair(1, 2, 'second'), new CompositePair(2, 1, 'third'));

    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('pair')->from(CompositePair::class, 'pair')->orderBy('pair.a', SortDirection::Ascending)->addOrderBy('pair.b', SortDirection::Ascending))
        ->columns([TextColumn::make('b')->summarize([Sum::make(), Count::make()])])
        ->paginated([2]));

    $query = $host->getPageTableSummaryQuery();

    expect($query?->getDQL())->toContain('((pair.a = :sylament_summary_keys_0_0 AND pair.b = :sylament_summary_keys_0_1) OR (pair.a = :sylament_summary_keys_1_0 AND pair.b = :sylament_summary_keys_1_1))')
        ->and($query?->getParameter('sylament_summary_keys_1_1')?->getValue())->toBe(2)
        ->and(DriverValues::numeric($host->getTableSummarySelectedState($query)))->toBe(['sy_summary_0' => 3, 'sy_summary_1' => 2])
        ->and(DriverValues::numeric($host->getTableSummarySelectedState($host->getAllTableSummaryQuery())))->toBe(['sy_summary_0' => 4, 'sy_summary_1' => 3]);
});

/*
 * The derived-identity flavour: one half of the key is an association, whose
 * component binds through the target's own identifier type — a `Handle` here, so
 * `apollo` reaches the statement as the string the column stores.
 */
it('narrows the page summary of a derived-identity table through its association field', function (): void {
    $entityManager = summaryStateResidencies();

    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('residency')->from(Residency::class, 'residency')->orderBy('residency.position', SortDirection::Ascending))
        ->columns([TextColumn::make('position')->summarize([Sum::make(), Count::make(), Average::make()])])
        ->paginated([2]));

    $query = $host->getPageTableSummaryQuery();

    expect($query?->getDQL())->toContain('((residency.venue = :sylament_summary_keys_0_0 AND residency.season = :sylament_summary_keys_0_1) OR (residency.venue = :sylament_summary_keys_1_0 AND residency.season = :sylament_summary_keys_1_1))')
        ->and($query?->getParameter('sylament_summary_keys_0_0')?->getValue())->toBe('apollo')
        ->and($query?->getParameter('sylament_summary_keys_0_1')?->getValue())->toBe('2026-spring')
        ->and(DriverValues::numeric($host->getTableSummarySelectedState($query)))->toBe(['sy_summary_0' => 30, 'sy_summary_1' => 2, 'sy_summary_2' => 15.0])
        ->and(DriverValues::numeric($host->getTableSummarySelectedState($host->getAllTableSummaryQuery())))->toBe(['sy_summary_0' => 100, 'sy_summary_1' => 4, 'sy_summary_2' => 25.0]);
});

/*
 * The un-narrowed answer for the keyed width, where the predicate it drops is the
 * expensive one: a term per record and a bound parameter per identifier field,
 * against a DQL parse superlinear in the key count.
 */
it('leaves a keyed page summary un-narrowed where one page holds the whole filtered set', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->query(summaryStateResidencies()->createQueryBuilder()->select('residency')->from(Residency::class, 'residency')->orderBy('residency.position', SortDirection::Ascending))
        ->columns([TextColumn::make('position')->summarize(Sum::make())])
        ->paginated(['all']));

    $host->tableRecordsPerPage = 'all';

    $query = $host->getPageTableSummaryQuery();

    expect(DriverValues::numeric($host->getTableSummarySelectedState($query)))->toBe(['sy_summary_0' => 100])
        ->and($query?->getDQL())->not->toContain('sylament_summary_keys')
        ->and($query?->getParameters()->toArray())->toBe([]);
});

it('answers no summary query at all on the records lane', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [['id' => '1', 'plays' => 10]])
        ->columns([TextColumn::make('plays')->summarize(Sum::make())]));

    expect($host->getAllTableSummaryQuery())->toBeNull()
        ->and($host->getPageTableSummaryQuery())->toBeNull();
});

/*
 * The batch is the reason the tier exists: every batchable summarizer of every visible
 * column rides one statement, each under its own deterministic alias.
 */
it('reads every batchable summarizer of every column in one statement', function (): void {
    $recorder = new RecordingMiddleware();
    $host = summaryStateHost(summaryStateSongs($recorder), [
        TextColumn::make('plays')->summarize([Sum::make(), Average::make()]),
        TextColumn::make('id')->summarize(Count::make()),
    ], recordsPerPage: 'all');

    $query = $host->getAllTableSummaryQuery();
    $recorder->reset();

    $state = $host->getTableSummarySelectedState($query);

    expect(DriverValues::numeric($state))->toBe(['sy_summary_0' => 210, 'sy_summary_1' => 35.0, 'sy_summary_2' => 6])
        ->and($recorder->statements())->toHaveCount(1)
        ->and($recorder->statements()[0])->not->toContain('GROUP BY');
});

it('hands each batched summarizer the alias its own figure comes back under', function (): void {
    $sum = Sum::make();
    $average = Average::make();
    $host = summaryStateHost(summaryStateSongs(), [TextColumn::make('plays')->summarize([$sum, $average])], recordsPerPage: 'all');

    $state = $host->getTableSummarySelectedState($host->getAllTableSummaryQuery());

    $sum->selectedState($state);
    $average->selectedState($state);

    expect($sum->getSelectedState())->toBe(210)
        ->and($average->getSelectedState())->toBe(35.0);
});

/*
 * The ordinal advances once per summarizer the batch pushed an alias onto, never once
 * per select emitted: an icon count takes an alias and contributes no statement, so
 * counting the statements instead would push that same alias onto the next summarizer
 * and hand the icon count its neighbour's figure — as a figure of its own, in place of
 * the refusal it owes.
 */
it('advances the batch alias per summarizer pushed rather than per select emitted', function (): void {
    $icons = Count::make()->icons();
    $sum = Sum::make();
    $host = summaryStateHost(summaryStateSongs(), [TextColumn::make('plays')->summarize([$icons, $sum])], recordsPerPage: 'all');

    $state = $host->getTableSummarySelectedState($host->getAllTableSummaryQuery());

    $icons->selectedState($state);
    $sum->selectedState($state);

    expect(DriverValues::numeric($state))->toBe(['sy_summary_1' => 210])
        ->and($sum->getSelectedState())->toBe(210)
        ->and($icons->hasSelectedState())->toBeFalse()
        ->and(fn (): mixed => $icons->getState())->toThrow(LogicException::class, 'IconColumn');
});

it('leaves a scoped summarizer out of the batch so it runs its own query', function (): void {
    $scoped = Count::make()->query(fn (QueryBuilder $query): QueryBuilder => $query->andWhere('song.plays > 30'));
    $host = summaryStateHost(summaryStateSongs(), [
        TextColumn::make('plays')->summarize([Sum::make(), $scoped]),
    ], recordsPerPage: 'all');

    $query = $host->getAllTableSummaryQuery();

    expect(DriverValues::numeric($host->getTableSummarySelectedState($query)))->toBe(['sy_summary_0' => 210])
        ->and($scoped->query($query)->getState())->toBe(3);
});

it('batches nothing when no summarizer emits a select statement', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [
        TextColumn::make('plays')->summarize([Range::make(), Values::make()]),
    ], recordsPerPage: 'all');

    expect($host->getTableSummarySelectedState($host->getAllTableSummaryQuery()))->toBe([]);
});

it('reads a to-one relation column off one join it reuses', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [
        TextColumn::make('artist.name')->summarize(Count::make()),
        TextColumn::make('artist.id')->summarize(Count::make()),
    ], recordsPerPage: 'all');

    $query = $host->getAllTableSummaryQuery();
    $state = $host->getTableSummarySelectedState($query);

    expect(DriverValues::numeric($state))->toBe(['sy_summary_0' => 6, 'sy_summary_1' => 6])
        ->and(mb_substr_count($query?->getDQL() ?? '', 'LEFT JOIN song.artist'))->toBe(1);
});

it('reads an embeddable path off the root alias', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [
        TextColumn::make('artist.address.street')->summarize(Count::make()),
    ], recordsPerPage: 'all');

    expect(DriverValues::numeric($host->getTableSummarySelectedState($host->getAllTableSummaryQuery())))->toBe(['sy_summary_0' => 6]);
});

it('resolves no expression for a column whose summarizers all sit out the batch', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [
        TextColumn::make('genres.name')->summarize(Values::make()),
        TextColumn::make('plays')->summarize(Sum::make()),
    ], recordsPerPage: 'all');

    expect(DriverValues::numeric($host->getTableSummarySelectedState($host->getAllTableSummaryQuery())))->toBe(['sy_summary_0' => 210]);
});

it('refuses a column summarized through a to-many association', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [
        TextColumn::make('genres.name')->summarize(Count::make()),
    ], recordsPerPage: 'all');

    expect(fn (): array => $host->getTableSummarySelectedState($host->getAllTableSummaryQuery()))
        ->toThrow(LogicException::class, 'Cannot summarize [genres.name]');
});

/*
 * A to-many join multiplies the rows every aggregate on the statement reads, so a
 * user's own joining query is refused rather than answered with an inflated figure.
 */
it('refuses to summarize over a query that joins a to-many association', function (): void {
    $entityManager = summaryStateSongs();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song')->leftJoin('song.genres', 'sy_genres'))
        ->columns([TextColumn::make('plays')->summarize(Sum::make())]));

    expect(fn (): array => $host->getTableSummarySelectedState($host->getAllTableSummaryQuery()))
        ->toThrow(LogicException::class, 'Summarizer::using()');
});

it('leaves an aggregate column out of the batch, where its refusal is read time', function (): void {
    $entityManager = summaryStateSongs();

    EntityManagerFactory::seed($entityManager, new Genre(1, 'Rock'));

    $host = summaryStateHost($entityManager, [
        TextColumn::make('genres_count')->counts('genres')->summarize(Sum::make()),
        TextColumn::make('plays')->summarize(Sum::make()),
    ], recordsPerPage: 'all');

    expect(DriverValues::numeric($host->getTableSummarySelectedState($host->getAllTableSummaryQuery())))->toBe(['sy_summary_0' => 210]);
});

/*
 * The band renders the visible columns, so the batch reads them too — a toggled-off
 * column contributes no aggregate to a statement whose value nothing would read.
 */
it('batches the visible columns rather than every declared one', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [
        TextColumn::make('plays')->summarize(Sum::make())->hidden(),
        TextColumn::make('id')->summarize(Count::make()),
    ], recordsPerPage: 'all');

    expect(DriverValues::numeric($host->getTableSummarySelectedState($host->getAllTableSummaryQuery())))->toBe(['sy_summary_0' => 6]);
});

it('hands back an independent builder on every read', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [TextColumn::make('plays')->summarize(Sum::make())]);

    $first = $host->getAllTableSummaryQuery();
    $second = $host->getAllTableSummaryQuery();

    $first?->andWhere('song.plays > 30');

    expect($first)->not->toBe($second)
        ->and($second?->getDQL())->not->toContain('song.plays > 30')
        ->and(DriverValues::numeric($host->getTableSummarySelectedState($second)))->toBe(['sy_summary_0' => 210]);
});

/**
 * The same six songs the ungrouped cases read, grouped by their artist and paged —
 * three per artist, so one group sums to 60 and the other to 150 against the whole
 * table's 210, and no page can be mistaken for a group or for the table.
 *
 * @param (Closure(Table): Table)|null $configure
 */
function groupedSummaryHost(EntityManager $entityManager, int|string|null $recordsPerPage = 2, int $page = 1, ?Closure $configure = null): TableHost
{
    $host = new TableHost(function (Table $table) use ($entityManager, $recordsPerPage, $configure): Table {
        $table = $table
            ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song')->orderBy('song.id', SortDirection::Ascending))
            ->columns([TextColumn::make('plays')->summarize(Sum::make())])
            ->groups(['artist.name', 'title']);

        $table = $recordsPerPage === null ? $table->paginated(false) : $table->paginated([$recordsPerPage]);

        return $configure instanceof Closure ? $configure($table) : $table;
    });

    $host->tableGrouping = 'artist.name';
    $host->tablePage = $page;

    return $host;
}

it('summarizes each group in one statement of its own', function (): void {
    $recorder = new RecordingMiddleware();
    $host = groupedSummaryHost(summaryStateSongs($recorder), recordsPerPage: null);

    $records = $host->getTableRecords();
    $recorder->reset();

    $radiohead = $host->getTableSummarySelectedState($host->getGroupedTableSummaryQuery($records[1]));
    $bjork = $host->getTableSummarySelectedState($host->getGroupedTableSummaryQuery($records[4]));

    expect(DriverValues::numeric($radiohead))->toBe(['sy_summary_0' => 60])
        ->and(DriverValues::numeric($bjork))->toBe(['sy_summary_0' => 150])
        ->and($recorder->statements())->toHaveCount(2);
});

it('scopes a group holding a single record', function (): void {
    $host = groupedSummaryHost(summaryStateSongs(), recordsPerPage: null);
    $host->tableGrouping = 'title';

    $records = $host->getTableRecords();

    expect(DriverValues::numeric($host->getTableSummarySelectedState($host->getGroupedTableSummaryQuery($records[1]))))->toBe(['sy_summary_0' => 10]);
});

it('honours the group scope closure on the grouped summary path', function (): void {
    $host = groupedSummaryHost(summaryStateSongs(), recordsPerPage: null, configure: fn (Table $table): Table => $table->groups([
        Group::make('artist.name')->scopeQueryUsing(fn (QueryBuilder $query): QueryBuilder => $query->andWhere('song.plays > 10')),
    ]));

    $records = $host->getTableRecords();

    expect(DriverValues::numeric($host->getTableSummarySelectedState($host->getGroupedTableSummaryQuery($records[1]))))->toBe(['sy_summary_0' => 200]);
});

it('answers no grouped summary query when nothing is grouped or nothing is queried', function (): void {
    $ungrouped = groupedSummaryHost(summaryStateSongs(), recordsPerPage: null);
    $ungrouped->tableGrouping = null;

    $records = $ungrouped->getTableRecords();

    $recordsLane = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [['id' => '1', 'plays' => 10]])
        ->columns([TextColumn::make('plays')->summarize(Sum::make())])
        ->groups(['plays']));
    $recordsLane->tableGrouping = 'plays';

    expect($ungrouped->getGroupedTableSummaryQuery($records[1]))->toBeNull()
        ->and($recordsLane->getGroupedTableSummaryQuery(['id' => '1', 'plays' => 10]))->toBeNull();
});

/*
 * The scope lands on a builder of the group's own, so the all-table row that reads
 * the next one is not quietly narrowed to whichever group asked first.
 */
it('scopes a fresh clone rather than the query the all-table row reads', function (): void {
    $host = groupedSummaryHost(summaryStateSongs(), recordsPerPage: null);

    $records = $host->getTableRecords();
    $grouped = $host->getGroupedTableSummaryQuery($records[1]);

    expect($grouped)->not->toBe($host->getAllTableSummaryQuery())
        ->and(DriverValues::numeric($host->getTableSummarySelectedState($grouped)))->toBe(['sy_summary_0' => 60])
        ->and(DriverValues::numeric($host->getTableSummarySelectedState($host->getAllTableSummaryQuery())))->toBe(['sy_summary_0' => 210]);
});

/*
 * A date group keys by the day, so its scope has to be the half-open range around
 * that day rather than an equality against an instant — two songs released hours
 * apart on one day summarize together.
 */
it('scopes a date group to the whole day its key names', function (): void {
    $entityManager = EntityManagerFactory::create();

    EntityManagerFactory::seed(
        $entityManager,
        new Song(id: 1, title: 'Airbag', plays: 10, releasedAt: new DateTimeImmutable('2026-09-02 09:00:00')),
        new Song(id: 2, title: 'Let Down', plays: 20, releasedAt: new DateTimeImmutable('2026-09-02 21:30:00')),
        new Song(id: 3, title: 'Jóga', plays: 30, releasedAt: new DateTimeImmutable('2026-09-03 08:00:00')),
    );

    $host = groupedSummaryHost($entityManager, recordsPerPage: null, configure: fn (Table $table): Table => $table->groups([
        Group::make('releasedAt')->date(),
    ]));
    $host->tableGrouping = 'releasedAt';

    $records = $host->getTableRecords();

    expect(DriverValues::numeric($host->getTableSummarySelectedState($host->getGroupedTableSummaryQuery($records[1]))))->toBe(['sy_summary_0' => 30])
        ->and(DriverValues::numeric($host->getTableSummarySelectedState($host->getGroupedTableSummaryQuery($records[3]))))->toBe(['sy_summary_0' => 30]);
});

it('renders no trailing grouped summary for a record that is not there', function (): void {
    $host = groupedSummaryHost(summaryStateSongs());

    expect($host->shouldRenderTrailingGroupedTableSummary(null))->toBeFalse();
});

it('renders the trailing grouped summary where no next page can continue the group', function (): void {
    $unpaginated = groupedSummaryHost(summaryStateSongs(), recordsPerPage: null);
    $lastPage = groupedSummaryHost(summaryStateSongs(), page: 3);
    $wholeTableOnOnePage = groupedSummaryHost(summaryStateSongs(), recordsPerPage: 'all');

    expect($unpaginated->shouldRenderTrailingGroupedTableSummary($unpaginated->getTableRecords()[6]))->toBeTrue()
        ->and($lastPage->shouldRenderTrailingGroupedTableSummary($lastPage->getTableRecords()->getRecords()[6]))->toBeTrue()
        ->and($wholeTableOnOnePage->shouldRenderTrailingGroupedTableSummary($wholeTableOnOnePage->getTableRecords()->getRecords()[6]))->toBeTrue();
});

it('renders the trailing grouped summary on a table that groups nothing', function (): void {
    $host = groupedSummaryHost(summaryStateSongs());
    $host->tableGrouping = null;

    expect($host->shouldRenderTrailingGroupedTableSummary($host->getTableRecords()->getRecords()[2]))->toBeTrue();
});

/*
 * The whole point of the probe: the first two songs are two thirds of one artist's
 * three, so summarizing that group here would put two records' figure under a
 * heading that names three.
 */
it('suppresses the trailing grouped summary when the next page continues the group', function (): void {
    $host = groupedSummaryHost(summaryStateSongs());

    expect($host->shouldRenderTrailingGroupedTableSummary($host->getTableRecords()->getRecords()[2]))->toBeFalse();
});

it('renders the trailing grouped summary when the next page starts a new group', function (): void {
    $host = groupedSummaryHost(summaryStateSongs(), recordsPerPage: 3);

    expect($host->shouldRenderTrailingGroupedTableSummary($host->getTableRecords()->getRecords()[3]))->toBeTrue();
});

/*
 * The paginator counted the rows before the look-ahead reads them, so a page that
 * claims a successor can still be followed by nothing at all.
 */
it('renders the trailing grouped summary when the next page turns out to be empty', function (): void {
    $entityManager = summaryStateSongs();
    $host = groupedSummaryHost($entityManager);

    $lastRecord = $host->getTableRecords()->getRecords()[2];

    $entityManager->createQuery('DELETE FROM ' . Song::class . ' song WHERE song.id > 2')->execute();

    expect($host->getTablePaginator()?->hasMorePages())->toBeTrue()
        ->and($host->shouldRenderTrailingGroupedTableSummary($lastRecord))->toBeTrue();
});

/*
 * A `date()` group keys by the day, so the look-ahead compares days too: two songs
 * released hours apart across a page break are still one group, and one released the
 * next morning is not.
 */
it('compares the days of a date group across the page break', function (): void {
    $entityManager = EntityManagerFactory::create();

    EntityManagerFactory::seed(
        $entityManager,
        new Song(id: 1, title: 'Airbag', plays: 10, releasedAt: new DateTimeImmutable('2026-09-02 09:00:00')),
        new Song(id: 2, title: 'Let Down', plays: 20, releasedAt: new DateTimeImmutable('2026-09-02 21:30:00')),
        new Song(id: 3, title: 'No Surprises', plays: 30, releasedAt: new DateTimeImmutable('2026-09-02 23:45:00')),
        new Song(id: 4, title: 'Jóga', plays: 40, releasedAt: new DateTimeImmutable('2026-09-03 01:15:00')),
    );

    $dated = fn (int $recordsPerPage): TableHost => groupedSummaryHost(
        $entityManager,
        recordsPerPage: $recordsPerPage,
        configure: fn (Table $table): Table => $table->groups([Group::make('releasedAt')->date()]),
    );

    $sameDay = $dated(2);
    $sameDay->tableGrouping = 'releasedAt';

    $nextDay = $dated(3);
    $nextDay->tableGrouping = 'releasedAt';

    expect($sameDay->shouldRenderTrailingGroupedTableSummary($sameDay->getTableRecords()->getRecords()[2]))->toBeFalse()
        ->and($nextDay->shouldRenderTrailingGroupedTableSummary($nextDay->getTableRecords()->getRecords()[3]))->toBeTrue();
});

it('costs one statement to look ahead, and none where an early return answers', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = summaryStateSongs($recorder);

    $host = groupedSummaryHost($entityManager);
    $lastRecord = $host->getTableRecords()->getRecords()[2];

    $recorder->reset();

    expect($host->shouldRenderTrailingGroupedTableSummary($lastRecord))->toBeFalse()
        ->and($recorder->statements())->toHaveCount(1);

    $lastPage = groupedSummaryHost($entityManager, page: 3);
    $lastPageRecord = $lastPage->getTableRecords()->getRecords()[6];

    $recorder->reset();

    expect($lastPage->shouldRenderTrailingGroupedTableSummary($lastPageRecord))->toBeTrue()
        ->and($recorder->statements())->toBe([]);
});

/*
 * A to-many fetch join makes rows and records different things, so the naive window
 * of one hands back a row of the record the page already ended on — the same trap
 * `paginateTableQuery()` routes around, and the same way out.
 */
it('reads past a fetch-joined to-many through the paginator', function (): void {
    $entityManager = EntityManagerFactory::create();

    $radiohead = new Artist(1, 'Radiohead');
    $portishead = new Artist(2, 'Portishead');
    $rock = new Genre(1, 'Rock');
    $tripHop = new Genre(2, 'Trip hop');
    $airbag = new Song(id: 1, title: 'Airbag', plays: 10, artist: $radiohead);

    // Two genres on the page's first song, so the fetch join makes the window of
    // one land inside that song rather than on the record after the page.
    $airbag->getGenres()->add($rock);
    $airbag->getGenres()->add($tripHop);

    EntityManagerFactory::seed(
        $entityManager,
        $radiohead,
        $portishead,
        $rock,
        $tripHop,
        $airbag,
        new Song(id: 2, title: 'Let Down', plays: 20, artist: $radiohead),
        new Song(id: 3, title: 'Glory Box', plays: 30, artist: $portishead),
        new Song(id: 4, title: 'Roads', plays: 40, artist: $portishead),
    );

    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()
            ->select('song', 'sy_genres')
            ->from(Song::class, 'song')
            ->leftJoin('song.genres', 'sy_genres')
            ->orderBy('song.id', SortDirection::Ascending))
        ->columns([TextColumn::make('plays')->summarize(Sum::make())])
        ->groups(['artist.name'])
        ->paginated([2]));
    $host->tableGrouping = 'artist.name';

    expect($host->shouldRenderTrailingGroupedTableSummary($host->getTableRecords()->getRecords()[2]))->toBeTrue();
});

/**
 * The four residencies grouped by season and ordered by it, one to a page — so
 * page 1 ends in the middle of a group and page 2 ends one, and the look-ahead
 * has to read a record either way. The tiebreak orders the two of a season by
 * venue, so which record follows which is fixed.
 *
 * @param (Closure(Table): Table)|null $configure
 */
function groupedResidencySummaryHost(EntityManager $entityManager, int $page = 1, ?Closure $configure = null): TableHost
{
    $host = new TableHost(function (Table $table) use ($entityManager, $configure): Table {
        $table = $table
            ->query($entityManager->createQueryBuilder()->select('residency')->from(Residency::class, 'residency')->orderBy('residency.season', SortDirection::Ascending))
            ->columns([TextColumn::make('position')->summarize(Sum::make())])
            ->groups(['season'])
            ->paginated([1]);

        return $configure instanceof Closure ? $configure($table) : $table;
    });

    $host->tableGrouping = 'season';
    $host->tablePage = $page;

    return $host;
}

/*
 * The per-group figure is one scoped statement per group, and the scope narrows by
 * the grouped column — so nothing in it touches the identifier, whatever the root
 * is keyed by. Pinned on the keyed flavour because nothing pinned it there.
 */
it('summarizes each group of a keyed table in one statement of its own', function (): void {
    $recorder = new RecordingMiddleware();
    $host = groupedResidencySummaryHost(summaryStateResidencies($recorder));

    $records = $host->getTableRecords()->getRecords();

    $recorder->reset();

    expect(DriverValues::numeric($host->getTableSummarySelectedState($host->getGroupedTableSummaryQuery($records['apollo~2026-autumn']))))->toBe(['sy_summary_0' => 60])
        ->and($recorder->statements())->toHaveCount(1)
        ->and($recorder->normalizedStatements()[0])->toContain('WHERE a0_.season = ?');
});

/*
 * Neither summarizer batches, so each runs a statement of its own over the page
 * query — the narrowed one on a keyed root, which is the pairing that had no pin.
 */
it('reads a range and a list off a keyed page', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->query(summaryStateResidencies()->createQueryBuilder()->select('residency')->from(Residency::class, 'residency')->orderBy('residency.position', SortDirection::Ascending))
        ->columns([TextColumn::make('position')->summarize([Range::make(), Values::make()])])
        ->paginated([2]));

    $column = TextColumn::make('position')->table($host->getTable());
    $range = Range::make()->column($column)->query($host->getPageTableSummaryQuery());
    $values = Values::make()->column($column)->query($host->getPageTableSummaryQuery());

    $listed = $values->getState();

    sort($listed);

    expect($range->getState())->toBe([10, 20])
        ->and($listed)->toBe([10, 20]);
});

/*
 * The look-ahead over a keyed root that joins nothing: a window of one at the next
 * page's offset, ordered deterministically by the identifier tiebreaker `CanSortRecords`
 * appends.
 */
it('looks past a keyed page to the group the next one starts', function (int $page, bool $rendered): void {
    $host = groupedResidencySummaryHost(summaryStateResidencies(), page: $page);
    $records = $host->getTableRecords()->getRecords();

    expect($host->shouldRenderTrailingGroupedTableSummary(array_first($records)))->toBe($rendered);
})->with([
    'a page ending mid-group' => [1, false],
    'a page ending its group' => [2, true],
]);

/*
 * The look-ahead reads its window the way the page reads its own, so a keyed root
 * whose query joins a collection is read in two steps here too — over a window of
 * one. In one render the page is read first, from a query whose joins are never
 * fewer than this one's, which is why reaching this site at all means joining the
 * builder after the page was already read and cached.
 */
it('looks past a keyed page whose query joins a to-many', function (int $page, bool $rendered): void {
    $entityManager = summaryStateResidencies();
    $query = $entityManager->createQueryBuilder()->select('residency')->from(Residency::class, 'residency')->orderBy('residency.season', SortDirection::Ascending);

    $host = groupedResidencySummaryHost($entityManager, page: $page, configure: fn (Table $table): Table => $table->query($query));
    $records = $host->getTableRecords()->getRecords();

    $query->addSelect('sy_gig_venue', 'sy_gigs')->leftJoin('residency.venue', 'sy_gig_venue')->leftJoin('sy_gig_venue.gigs', 'sy_gigs');

    expect($host->getTablePaginator()?->hasMorePages())->toBeTrue()
        ->and($host->shouldRenderTrailingGroupedTableSummary(array_first($records)))->toBe($rendered);
})->with([
    'a page ending mid-group' => [1, false],
    'a page ending its group' => [2, true],
]);

/*
 * The look-ahead reaches the keyed read the page reaches, so the refusal reaches
 * it too — and no page read stands in front of this one, the join and the hint
 * both arriving after the page was read and cached. The join is what makes the
 * refusal real: without it the look-ahead is a plain window on a keyed root,
 * which Doctrine serves and the guard deliberately leaves alone.
 */
it('refuses the trailing-group look-ahead where a joined query sets its own output walker', function (): void {
    $entityManager = summaryStateResidencies();
    $query = $entityManager->createQueryBuilder()->select('residency')->from(Residency::class, 'residency')->orderBy('residency.season', SortDirection::Ascending);

    $host = groupedResidencySummaryHost($entityManager, configure: fn (Table $table): Table => $table->query($query));
    $lastRecord = array_first($host->getTableRecords()->getRecords());

    $query
        ->addSelect('sy_gig_venue', 'sy_gigs')
        ->leftJoin('residency.venue', 'sy_gig_venue')
        ->leftJoin('sy_gig_venue.gigs', 'sy_gigs')
        ->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, CountOutputWalker::class);

    expect($host->getTablePaginator()?->hasMorePages())->toBeTrue()
        ->and(fn (): bool => $host->shouldRenderTrailingGroupedTableSummary($lastRecord))
        ->toThrow(LogicException::class, 'sets its own output walker [Doctrine\ORM\Tools\Pagination\CountOutputWalker]');
});
