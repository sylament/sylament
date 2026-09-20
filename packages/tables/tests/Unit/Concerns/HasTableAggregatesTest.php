<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Sylament\Support\Pagination\Paginator;
use Sylament\Tables\Columns\Column;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Gig;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Locker;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\LockerItem;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\LockerSeal;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\ResidencyShow;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\ShortcutHit;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;
use Sylament\Tables\Tests\Fixtures\Doctrine\RecordingMiddleware;

/**
 * Radiohead with two songs, Adele with none, Björk with one — paged two at a
 * time, so the first page carries both a record the batch answers for and one
 * it leaves absent.
 */
function artistsWithSongs(?RecordingMiddleware $recorder = null): EntityManager
{
    $entityManager = EntityManagerFactory::create($recorder);

    $radiohead = new Artist(1, 'Radiohead');
    $adele = new Artist(2, 'Adele');
    $bjork = new Artist(3, 'Björk');

    EntityManagerFactory::seed(
        $entityManager,
        $radiohead,
        $adele,
        $bjork,
        new Song(1, 'Airbag', 10, $radiohead),
        new Song(2, 'Let Down', 20, $radiohead),
        new Song(3, 'Jóga', 300, $bjork),
    );

    return $entityManager;
}

/**
 * @param array<Column> $columns
 */
function artistTableHost(EntityManager $entityManager, array $columns, int|string $recordsPerPage = 2): TableHost
{
    return new TableHost(function (Table $table) use ($entityManager, $columns, $recordsPerPage): Table {
        $table = $table
            ->query($entityManager->createQueryBuilder()->select('artist')->from(Artist::class, 'artist')->orderBy('artist.id', 'ASC'))
            ->columns($columns);

        return $recordsPerPage === 'all'
            ? $table->paginated([10, 'all'])->defaultPaginationPageOption('all')
            : $table->paginated([$recordsPerPage]);
    });
}

/**
 * Four artists whose song counts run against their id order — Adele 1,
 * Radiohead 3, Sade 0, Björk 2 — so an id-ordered page and a count-ordered one
 * can never be mistaken for each other.
 */
function artistsBySongCount(?RecordingMiddleware $recorder = null): EntityManager
{
    $entityManager = EntityManagerFactory::create($recorder);

    $adele = new Artist(1, 'Adele');
    $radiohead = new Artist(2, 'Radiohead');
    $sade = new Artist(3, 'Sade');
    $bjork = new Artist(4, 'Björk');

    EntityManagerFactory::seed(
        $entityManager,
        $adele,
        $radiohead,
        $sade,
        $bjork,
        new Song(1, 'Hello', 10, $adele),
        new Song(2, 'Airbag', 20, $radiohead),
        new Song(3, 'Let Down', 30, $radiohead),
        new Song(4, 'No Surprises', 40, $radiohead),
        new Song(5, 'Jóga', 500, $bjork),
        new Song(6, 'Hyperballad', 600, $bjork),
    );

    return $entityManager;
}

/**
 * A host over the unordered artist query, so the sort under test is the only
 * ordering the page has beyond the identifier tiebreak the host appends.
 *
 * @param array<Column> $columns
 */
function sortedArtistTableHost(EntityManager $entityManager, array $columns, string $sort, int $page = 1, int $recordsPerPage = 2): TableHost
{
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('artist')->from(Artist::class, 'artist'))
        ->columns($columns)
        ->paginated([$recordsPerPage]));

    $host->tableSort = $sort;
    $host->tablePage = $page;

    return $host;
}

/**
 * The named column's cell state on every row of the resolved page.
 *
 * @return list<mixed>
 */
function aggregateCellStates(TableHost $host, string $columnName): array
{
    $records = $host->getTableRecords();
    $column = $host->getTable()->getColumn($columnName);
    $states = [];

    foreach ($records instanceof Paginator ? $records->getRecords() : $records as $recordKey => $record) {
        $states[] = $column?->record($record)->recordKey($recordKey)->getState();
    }

    return $states;
}

it('fills the store for the page and the cell reads it', function (): void {
    $host = artistTableHost(artistsWithSongs(), [TextColumn::make('songs_count')->counts('songs')]);

    expect(aggregateCellStates($host, 'songs_count'))->toBe([2, 0]);
});

it('exists reads as a real bool with false for absent keys', function (): void {
    $host = artistTableHost(artistsWithSongs(), [TextColumn::make('songs_exists')->exists('songs')]);

    expect(aggregateCellStates($host, 'songs_exists'))->toBe([true, false]);
});

it('avg reads null for a record with no related rows', function (): void {
    $host = artistTableHost(artistsWithSongs(), [TextColumn::make('songs_avg_plays')->avg('songs', 'plays')]);

    expect(aggregateCellStates($host, 'songs_avg_plays'))->toBe([15.0, null]);
});

it('adds no aggregate artifacts to the page or count queries', function (): void {
    $recorder = new RecordingMiddleware();
    $host = artistTableHost(artistsWithSongs($recorder), [TextColumn::make('songs_count')->counts('songs')]);

    $recorder->reset();

    $states = aggregateCellStates($host, 'songs_count');
    $statements = $recorder->statements();

    expect($states)->toBe([2, 0])
        ->and($statements)->toHaveCount(3)
        ->and($statements[0])->toContain('LIMIT')
        ->and($statements[0])->not->toContain('GROUP BY')
        ->and($statements[0])->not->toContain('COUNT(')
        ->and($statements[1])->toContain('GROUP BY')
        ->and($statements[2])->toContain('COUNT(')
        ->and($statements[2])->not->toContain('GROUP BY');
});

it('binds a declared scope closure through the column evaluator', function (): void {
    $scope = static fn (string $alias, QueryBuilder $query): QueryBuilder => $query->andWhere("{$alias}.plays > 100");
    $host = artistTableHost(artistsWithSongs(), [TextColumn::make('songs_count')->counts(['songs' => $scope])], recordsPerPage: 'all');

    expect(aggregateCellStates($host, 'songs_count'))->toBe([0, 0, 1]);
});

it('leaves an inert declaration to the record walk', function (): void {
    $recorder = new RecordingMiddleware();
    $host = artistTableHost(artistsWithSongs($recorder), [TextColumn::make('name')->counts(['songs as songs_count'])]);
    $column = $host->getTable()->getColumn('name');

    $recorder->reset();

    expect($column?->hasRelationshipAggregates())->toBeTrue()
        ->and($column?->getCellAggregateDeclaration())->toBeNull()
        ->and(aggregateCellStates($host, 'name'))->toBe(['Radiohead', 'Adele'])
        ->and($recorder->statements())->toHaveCount(2);
});

it('fills a table whose records carry an object identifier', function (): void {
    $entityManager = EntityManagerFactory::create();
    $arena = new Venue(new Handle('arena'), 'The Arena');
    $shed = new Venue(new Handle('shed'), 'The Shed');

    EntityManagerFactory::seed(
        $entityManager,
        $arena,
        $shed,
        new Gig(1, 'Opening', 400, $arena),
        new Gig(2, 'Encore', 900, $arena),
    );

    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('venue')->from(Venue::class, 'venue')->orderBy('venue.handle', 'ASC'))
        ->columns([TextColumn::make('gigs_count')->counts('gigs')])
        ->paginated(false));

    expect(aggregateCellStates($host, 'gigs_count'))->toBe([2, 0]);
});

it("fills on the 'all' per-page arm too", function (): void {
    $host = artistTableHost(artistsWithSongs(), [TextColumn::make('songs_count')->counts('songs')], recordsPerPage: 'all');

    expect(aggregateCellStates($host, 'songs_count'))->toBe([2, 0, 1]);
});

it('sorts by a counts column across a page boundary', function (): void {
    $entityManager = artistsBySongCount();
    $columns = static fn (): array => [
        TextColumn::make('name'),
        TextColumn::make('songs_count')->counts('songs')->sortable(),
    ];

    $firstPage = sortedArtistTableHost($entityManager, $columns(), 'songs_count:desc');
    $secondPage = sortedArtistTableHost($entityManager, $columns(), 'songs_count:desc', page: 2);

    expect(aggregateCellStates($firstPage, 'name'))->toBe(['Radiohead', 'Björk'])
        ->and(aggregateCellStates($firstPage, 'songs_count'))->toBe([3, 2])
        ->and(aggregateCellStates($secondPage, 'name'))->toBe(['Adele', 'Sade'])
        ->and(aggregateCellStates($secondPage, 'songs_count'))->toBe([1, 0])
        ->and($firstPage->getAllTableRecordsCount())->toBe(4)
        ->and($secondPage->getAllTableRecordsCount())->toBe(4);
});

it('sorts ascending with the identifier tiebreak intact', function (): void {
    $entityManager = artistsBySongCount();
    $columns = static fn (): array => [
        TextColumn::make('name'),
        TextColumn::make('songs_count')->counts('songs')->sortable(),
    ];

    $firstPage = sortedArtistTableHost($entityManager, $columns(), 'songs_count:asc');
    $secondPage = sortedArtistTableHost($entityManager, $columns(), 'songs_count:asc', page: 2);
    $dql = $firstPage->getFilteredSortedTableQuery()?->getDQL();

    expect(aggregateCellStates($firstPage, 'name'))->toBe(['Sade', 'Adele'])
        ->and(aggregateCellStates($secondPage, 'name'))->toBe(['Björk', 'Radiohead'])
        ->and(aggregateCellStates($secondPage, 'songs_count'))->toBe([2, 3])
        ->and($dql)->toContain('AS HIDDEN sy_agg1')
        ->and($dql)->toContain('ORDER BY sy_agg1 asc, artist.id asc');
});

it('sorts a fetch-joined collection query through the limit-subquery walker', function (): void {
    $entityManager = artistsBySongCount();
    $host = static function (int $page) use ($entityManager): TableHost {
        $host = new TableHost(fn (Table $table): Table => $table
            ->query($entityManager->createQueryBuilder()
                ->select('artist')
                ->addSelect('song')
                ->from(Artist::class, 'artist')
                ->leftJoin('artist.songs', 'song'))
            ->columns([TextColumn::make('name'), TextColumn::make('songs_count')->counts('songs')->sortable()])
            ->paginated([2]));

        $host->tableSort = 'songs_count:desc';
        $host->tablePage = $page;

        return $host;
    };

    $firstPage = $host(1);
    $secondPage = $host(2);

    expect(aggregateCellStates($firstPage, 'name'))->toBe(['Radiohead', 'Björk'])
        ->and(aggregateCellStates($firstPage, 'songs_count'))->toBe([3, 2])
        ->and(aggregateCellStates($secondPage, 'name'))->toBe(['Adele', 'Sade'])
        ->and($firstPage->getAllTableRecordsCount())->toBe(4);
});

it('keeps the count query free of the hidden select', function (): void {
    $recorder = new RecordingMiddleware();
    $host = sortedArtistTableHost(
        artistsBySongCount($recorder),
        [TextColumn::make('songs_count')->counts('songs')->sortable()],
        'songs_count:desc',
    );

    $recorder->reset();

    $total = $host->getAllTableRecordsCount();
    $statements = $recorder->statements();

    expect($total)->toBe(4)
        ->and($host->getFilteredSortedTableQuery()?->getDQL())->toContain('AS HIDDEN')
        ->and($host->getFilteredTableQuery()?->getDQL())->not->toContain('HIDDEN')
        ->and($statements)->toHaveCount(1)
        ->and($statements[0])->toContain('COUNT(')
        ->and($statements[0])->not->toContain('Song')
        ->and($statements[0])->not->toContain('ORDER BY');
});

it('carries a scoped aggregate sort and its parameters onto the page query', function (): void {
    $entityManager = artistsBySongCount();
    $scope = static fn (string $alias, QueryBuilder $query): QueryBuilder => $query
        ->andWhere("{$alias}.plays > :sylament_test_plays")
        ->setParameter('sylament_test_plays', 100);
    $columns = static fn (): array => [
        TextColumn::make('name'),
        TextColumn::make('songs_count')->counts(['songs' => $scope])->sortable(),
    ];

    $firstPage = sortedArtistTableHost($entityManager, $columns(), 'songs_count:desc');
    $secondPage = sortedArtistTableHost($entityManager, $columns(), 'songs_count:desc', page: 2);

    expect(aggregateCellStates($firstPage, 'name'))->toBe(['Björk', 'Sade'])
        ->and(aggregateCellStates($firstPage, 'songs_count'))->toBe([2, 0])
        ->and(aggregateCellStates($secondPage, 'name'))->toBe(['Radiohead', 'Adele'])
        ->and(aggregateCellStates($secondPage, 'songs_count'))->toBe([0, 0]);
});

it('keeps the correlation when a scope replaces the where clause', function (): void {
    $entityManager = artistsBySongCount();
    $scope = static fn (string $alias, QueryBuilder $query): QueryBuilder => $query->where("{$alias}.plays > 15");
    $columns = static fn (): array => [
        TextColumn::make('name'),
        TextColumn::make('songs_count')->counts(['songs' => $scope])->sortable(),
    ];

    $firstPage = sortedArtistTableHost($entityManager, $columns(), 'songs_count:desc');
    $secondPage = sortedArtistTableHost($entityManager, $columns(), 'songs_count:desc', page: 2);

    expect(aggregateCellStates($firstPage, 'name'))->toBe(['Radiohead', 'Björk'])
        ->and(aggregateCellStates($firstPage, 'songs_count'))->toBe([3, 2])
        ->and(aggregateCellStates($secondPage, 'name'))->toBe(['Sade', 'Adele'])
        ->and(aggregateCellStates($secondPage, 'songs_count'))->toBe([0, 0]);
});

it('honours an explicit sort-column list over the aggregate', function (): void {
    $host = sortedArtistTableHost(
        artistsBySongCount(),
        [TextColumn::make('name'), TextColumn::make('songs_count')->counts('songs')->sortable(['name'])],
        'songs_count:asc',
    );
    $dql = $host->getFilteredSortedTableQuery()?->getDQL();

    expect(aggregateCellStates($host, 'name'))->toBe(['Adele', 'Björk'])
        ->and(aggregateCellStates($host, 'songs_count'))->toBe([1, 2])
        ->and($dql)->toContain('ORDER BY artist.name asc')
        ->and($dql)->not->toContain('HIDDEN');
});

it('a searched, aggregate-sorted, paginated table stays consistent', function (): void {
    $entityManager = artistsBySongCount();
    $columns = static fn (): array => [
        TextColumn::make('name')->searchable(),
        TextColumn::make('songs_count')->counts('songs')->sortable(),
    ];

    $firstPage = sortedArtistTableHost($entityManager, $columns(), 'songs_count:desc');
    $firstPage->tableSearch = 'a';

    $secondPage = sortedArtistTableHost($entityManager, $columns(), 'songs_count:desc', page: 2);
    $secondPage->tableSearch = 'a';

    expect(aggregateCellStates($firstPage, 'name'))->toBe(['Radiohead', 'Adele'])
        ->and(aggregateCellStates($firstPage, 'songs_count'))->toBe([3, 1])
        ->and(aggregateCellStates($secondPage, 'name'))->toBe(['Sade'])
        ->and(aggregateCellStates($secondPage, 'songs_count'))->toBe([0])
        ->and($firstPage->getAllTableRecordsCount())->toBe(3);
});

it('runs no aggregate query for a toggled-hidden column', function (): void {
    $recorder = new RecordingMiddleware();
    $host = artistTableHost(artistsWithSongs($recorder), [TextColumn::make('songs_count')->counts('songs')->toggleable()]);
    $host->tableColumns = [['type' => 'column', 'name' => 'songs_count', 'isToggled' => false, 'isToggledHiddenByDefault' => false]];

    $recorder->reset();

    $records = $host->getTableRecords();
    $statements = $recorder->statements();

    expect($records)->toBeInstanceOf(Paginator::class)
        ->and($statements)->toHaveCount(2)
        ->and($statements[0])->toContain('LIMIT')
        ->and($statements[1])->toContain('COUNT(')
        ->and(array_filter($statements, static fn (string $statement): bool => str_contains($statement, 'GROUP BY')))->toBe([]);
});

function aggregatedShortcut(string $host, string $path, string $label): Shortcut
{
    $shortcut = new Shortcut();
    $shortcut->setHost($host);
    $shortcut->setPath($path);
    $shortcut->setLabel($label);

    return $shortcut;
}

/**
 * Four scalar-pair records whose hit counts run against their key order — A 0,
 * B 3, C 1, D 2 — so a key-ordered page and a count-ordered one can never be
 * mistaken for each other. The first key carries the characters an unescaped
 * one would lose.
 */
function aggregatedShortcuts(): EntityManager
{
    $entityManager = EntityManagerFactory::create();

    $a = aggregatedShortcut('a.test', '/x y', 'A');
    $b = aggregatedShortcut('b.test', '/2', 'B');
    $c = aggregatedShortcut('c.test', '/3', 'C');
    $d = aggregatedShortcut('d.test', '/4', 'D');

    EntityManagerFactory::seed(
        $entityManager,
        $a,
        $b,
        $c,
        $d,
        new ShortcutHit(1, 'curl', 1, $b),
        new ShortcutHit(2, 'curl', 2, $b),
        new ShortcutHit(3, 'curl', 3, $b),
        new ShortcutHit(4, 'curl', 4, $c),
        new ShortcutHit(5, 'curl', 5, $d),
        new ShortcutHit(6, 'curl', 6, $d),
    );

    return $entityManager;
}

function aggregatedResidency(Venue $venue, string $season, string $label): Residency
{
    $residency = new Residency();
    $residency->setVenue($venue);
    $residency->setSeason($season);
    $residency->setLabel($label);

    return $residency;
}

/**
 * Three derived-identity records — autumn 2 shows, empty none, winter 1 — so
 * the count order and the season order disagree.
 */
function aggregatedResidencies(): EntityManager
{
    $entityManager = EntityManagerFactory::create();
    $arena = new Venue(new Handle('arena'), 'The Arena');
    $shed = new Venue(new Handle('shed'), 'The Shed');
    $autumn = aggregatedResidency($arena, 'autumn/2026', 'Autumn');
    $empty = aggregatedResidency($arena, 'empty', 'Empty');
    $winter = aggregatedResidency($shed, 'winter', 'Winter');

    EntityManagerFactory::seed(
        $entityManager,
        $arena,
        $shed,
        $autumn,
        $empty,
        $winter,
        new ResidencyShow(1, 'Night one', 120, $autumn),
        new ResidencyShow(2, 'Night two', 180, $autumn),
        new ResidencyShow(3, 'Matinee', 60, $winter),
    );

    return $entityManager;
}

/**
 * Two seals over a type that stores `ab-1` and reads back `AB-1`: the store is
 * keyed by the record's own spelling, so a batch keyed off the raw column
 * leaves every cell on its default.
 */
function aggregatedLockers(): EntityManager
{
    $entityManager = EntityManagerFactory::create();
    $front = new Locker('AB-1', 'Front');
    $back = new Locker('CD-2', 'Back');

    EntityManagerFactory::seed(
        $entityManager,
        $front,
        $back,
        new LockerItem(1, 'Boots', 4, $front),
        new LockerItem(2, 'Rope', 9, $front),
        new LockerItem(3, 'Helmet', 2, $back),
        new LockerSeal($front, 'Sealed', 1),
        new LockerSeal($back, 'Open', 2),
    );

    return $entityManager;
}

/**
 * @param array<Column> $columns
 */
function compositeAggregateHost(EntityManager $entityManager, string $recordClass, string $alias, string $orderBy, array $columns): TableHost
{
    return new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select($alias)->from($recordClass, $alias)->orderBy("{$alias}.{$orderBy}", 'ASC'))
        ->columns($columns)
        ->paginated(false));
}

/**
 * @param array<Column> $columns
 */
function sortedCompositeAggregateHost(EntityManager $entityManager, string $recordClass, string $alias, array $columns, string $sort, int $page = 1): TableHost
{
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select($alias)->from($recordClass, $alias))
        ->columns($columns)
        ->paginated([2]));

    $host->tableSort = $sort;
    $host->tablePage = $page;

    return $host;
}

it('fills the store for a composite-identifier page and the cell reads it', function (): void {
    $host = compositeAggregateHost(aggregatedShortcuts(), Shortcut::class, 'shortcut', 'host', [
        TextColumn::make('hits_count')->counts('hits'),
    ]);

    expect(aggregateCellStates($host, 'hits_count'))->toBe([0, 3, 1, 2]);
});

it('reads exists as a real bool for a composite key with no related rows', function (): void {
    $host = compositeAggregateHost(aggregatedShortcuts(), Shortcut::class, 'shortcut', 'host', [
        TextColumn::make('hits_exists')->exists('hits'),
    ]);

    expect(aggregateCellStates($host, 'hits_exists'))->toBe([false, true, true, true]);
});

it('fills the store for a derived-identity page', function (): void {
    $host = compositeAggregateHost(aggregatedResidencies(), Residency::class, 'residency', 'season', [
        TextColumn::make('shows_count')->counts('shows'),
        TextColumn::make('shows_sum_seats')->sum('shows', 'seats'),
    ]);

    expect(aggregateCellStates($host, 'shows_count'))->toBe([2, 0, 1])
        ->and(aggregateCellStates($host, 'shows_sum_seats'))->toBe([300, null, 60]);
});

it('keys the store of a single derived identity by the identifier the cell asks for', function (): void {
    $host = compositeAggregateHost(aggregatedLockers(), LockerSeal::class, 'seal', 'position', [
        TextColumn::make('items_count')->counts('locker.items'),
    ]);

    expect(aggregateCellStates($host, 'items_count'))->toBe([2, 1]);
});

it('runs one aggregate statement per composite page and leaves the page query alone', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);
    $shortcut = aggregatedShortcut('one.test', '/a b', 'One');

    EntityManagerFactory::seed($entityManager, $shortcut, new ShortcutHit(1, 'curl', 1, $shortcut));

    $host = compositeAggregateHost($entityManager, Shortcut::class, 'shortcut', 'host', [
        TextColumn::make('hits_count')->counts('hits'),
    ]);

    $recorder->reset();

    $states = aggregateCellStates($host, 'hits_count');
    $statements = $recorder->statements();

    expect($states)->toBe([1])
        ->and($statements)->toHaveCount(2)
        ->and($statements[0])->not->toContain('GROUP BY')
        ->and($statements[1])->toContain('GROUP BY s0_.host, s0_.path');
});

it('sorts a composite-identifier table by an aggregate across a page boundary', function (): void {
    $entityManager = aggregatedShortcuts();
    $columns = static fn (): array => [
        TextColumn::make('label'),
        TextColumn::make('hits_count')->counts('hits')->sortable(),
    ];

    $firstPage = sortedCompositeAggregateHost($entityManager, Shortcut::class, 'shortcut', $columns(), 'hits_count:desc');
    $secondPage = sortedCompositeAggregateHost($entityManager, Shortcut::class, 'shortcut', $columns(), 'hits_count:desc', page: 2);
    $dql = $firstPage->getFilteredSortedTableQuery()?->getDQL();

    expect(aggregateCellStates($firstPage, 'label'))->toBe(['B', 'D'])
        ->and(aggregateCellStates($firstPage, 'hits_count'))->toBe([3, 2])
        ->and(aggregateCellStates($secondPage, 'label'))->toBe(['C', 'A'])
        ->and(aggregateCellStates($secondPage, 'hits_count'))->toBe([1, 0])
        ->and($firstPage->getAllTableRecordsCount())->toBe(4)
        ->and($dql)->toContain('sy_ag1.host = shortcut.host AND sy_ag1.path = shortcut.path')
        ->and($dql)->toContain('ORDER BY sy_agg1 desc, shortcut.host desc, shortcut.path desc');
});

it('sorts a derived-identity table by an aggregate', function (): void {
    $entityManager = aggregatedResidencies();
    $columns = static fn (): array => [
        TextColumn::make('label'),
        TextColumn::make('shows_count')->counts('shows')->sortable(),
    ];

    $firstPage = sortedCompositeAggregateHost($entityManager, Residency::class, 'residency', $columns(), 'shows_count:desc');
    $secondPage = sortedCompositeAggregateHost($entityManager, Residency::class, 'residency', $columns(), 'shows_count:desc', page: 2);
    $dql = $firstPage->getFilteredSortedTableQuery()?->getDQL();

    expect(aggregateCellStates($firstPage, 'label'))->toBe(['Autumn', 'Winter'])
        ->and(aggregateCellStates($firstPage, 'shows_count'))->toBe([2, 1])
        ->and(aggregateCellStates($secondPage, 'label'))->toBe(['Empty'])
        ->and(aggregateCellStates($secondPage, 'shows_count'))->toBe([0])
        ->and($dql)->toContain('sy_ag1.venue = residency.venue AND sy_ag1.season = residency.season');
});
