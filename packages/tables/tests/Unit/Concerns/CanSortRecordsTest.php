<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Book;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;
use Sylament\Testing\Doctrine\TestPlatform;

/*
 * Covers the query half of host-side sorting — the `ORDER BY` the lane composes.
 * The `sortTable()` state cycle it reads stays in `HasTableTest`.
 */

function seededSongs(): EntityManager
{
    $entityManager = EntityManagerFactory::create();

    EntityManagerFactory::seed(
        $entityManager,
        new Song(1, 'Charlie', 30),
        new Song(2, 'Alpha', 10),
        new Song(3, 'Bravo', 20),
    );

    return $entityManager;
}

/**
 * Two songs on artists that sort against each other and one on no artist at
 * all, so the row a LEFT JOIN leaves null has somewhere to land.
 */
function seededSongsByArtist(): EntityManager
{
    $entityManager = EntityManagerFactory::create();

    $zeta = new Artist(1, 'Zeta');
    $alpha = new Artist(2, 'Alpha');

    EntityManagerFactory::seed(
        $entityManager,
        $zeta,
        $alpha,
        new Song(1, 'Aria', 10, $zeta),
        new Song(2, 'Bolero', 20, $alpha),
        new Song(3, 'Cantata', 30),
    );

    return $entityManager;
}

/**
 * Two residencies of one venue and one of another, so a tiebreak over the
 * identifier association has something to separate before it reaches the season.
 */
function seededResidenciesByVenue(): EntityManager
{
    $entityManager = EntityManagerFactory::create();
    $arena = new Venue(new Handle('arena'), 'The Arena');
    $shed = new Venue(new Handle('shed'), 'The Shed');
    $residency = static function (Venue $venue, string $season, string $label): Residency {
        $record = new Residency();
        $record->setVenue($venue);
        $record->setSeason($season);
        $record->setLabel($label);

        return $record;
    };

    EntityManagerFactory::seed(
        $entityManager,
        $arena,
        $shed,
        $residency($shed, 'summer', 'Shed summer'),
        $residency($arena, 'winter', 'Arena winter'),
        $residency($arena, 'summer', 'Arena summer'),
    );

    return $entityManager;
}

/**
 * @return list<string>
 */
function sortedSongTitles(TableHost $host): array
{
    $titles = [];

    foreach ($host->getTableRecords() as $record) {
        $titles[] = $record->getTitle();
    }

    return $titles;
}

it('orders by the user sort with the identifier tiebreaker appended', function (): void {
    $entityManager = seededSongs();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('title')->sortable()]));

    $host->sortTable('title');

    expect(sortedSongTitles($host))->toBe(['Alpha', 'Bravo', 'Charlie'])
        ->and(array_keys($host->getTableRecords()->getRecords()))->toBe([2, 3, 1])
        ->and($host->getFilteredSortedTableQuery()?->getDQL())->toContain('ORDER BY song.title asc, song.id asc');
});

it('applies the default sort when the user has not sorted', function (): void {
    $entityManager = seededSongs();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('title')->sortable(), TextColumn::make('plays')->sortable()])
        ->defaultSort('plays', 'desc'));

    expect(sortedSongTitles($host))->toBe(['Charlie', 'Bravo', 'Alpha'])
        ->and($host->getFilteredSortedTableQuery()?->getDQL())->toContain('ORDER BY song.plays desc, song.id desc');
});

it('lowercases a mixed-case default sort direction', function (): void {
    $entityManager = seededSongs();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('title')->sortable(), TextColumn::make('plays')->sortable()])
        ->defaultSort('plays', 'DESC'));

    expect($host->getTable()->getDefaultSortDirection())->toBe('desc')
        ->and($host->getFilteredSortedTableQuery()?->getDQL())->toContain('ORDER BY song.plays desc, song.id desc')
        ->and(sortedSongTitles($host))->toBe(['Charlie', 'Bravo', 'Alpha']);
});

it('appends the default sort as a secondary order under a user sort', function (): void {
    $entityManager = seededSongs();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('title')->sortable(), TextColumn::make('plays')->sortable()])
        ->defaultSort('plays'));

    $host->sortTable('title');

    expect($host->getFilteredSortedTableQuery()?->getDQL())->toContain('ORDER BY song.title asc, song.plays asc, song.id asc')
        ->and(sortedSongTitles($host))->toBe(['Alpha', 'Bravo', 'Charlie']);
});

it('orders everything after the user sort in the declared default direction', function (): void {
    $entityManager = seededSongs();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('title')->sortable(), TextColumn::make('plays')->sortable()])
        ->defaultSort('plays'));

    $host->sortTable('title', 'desc');

    expect($host->getFilteredSortedTableQuery()?->getDQL())->toContain('ORDER BY song.title desc, song.plays asc, song.id asc')
        ->and(sortedSongTitles($host))->toBe(['Charlie', 'Bravo', 'Alpha']);
});

it('does not double-apply a default sort naming the sorted column', function (): void {
    $entityManager = seededSongs();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('title')->sortable()])
        ->defaultSort('title'));

    $host->sortTable('title');

    $dql = $host->getFilteredSortedTableQuery()?->getDQL() ?? '';

    expect($dql)->toContain('ORDER BY song.title asc, song.id asc')
        ->and(mb_substr_count($dql, 'song.title'))->toBe(1)
        ->and(sortedSongTitles($host))->toBe(['Alpha', 'Bravo', 'Charlie']);
});

it('resolves a default sort that is not a column through the path resolver', function (): void {
    $entityManager = seededSongs();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('title')->sortable()])
        ->defaultSort('plays'));

    expect($host->getFilteredSortedTableQuery()?->getDQL())->toContain('ORDER BY song.plays asc, song.id asc')
        ->and(sortedSongTitles($host))->toBe(['Alpha', 'Bravo', 'Charlie']);
});

it('lets a default sort closure order the query itself', function (): void {
    $entityManager = seededSongs();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('title')->sortable()])
        ->defaultSort(function (QueryBuilder $query, string $direction): void {
            $query->addOrderBy('song.plays', $direction);
        }, 'desc'));

    expect($host->getFilteredSortedTableQuery()?->getDQL())->toContain('ORDER BY song.plays desc, song.id desc')
        ->and(sortedSongTitles($host))->toBe(['Charlie', 'Bravo', 'Alpha']);
});

it('lets a default sort closure replace the query', function (): void {
    $entityManager = seededSongs();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('title')->sortable()])
        ->defaultSort(fn (): QueryBuilder => $entityManager->createQueryBuilder()
            ->select('song')
            ->from(Song::class, 'song')
            ->andWhere('song.plays > 15')));

    $records = $host->getTableRecords()->getRecords();

    expect($records)->toHaveCount(2)
        ->and(array_keys($records))->toBe([1, 3])
        ->and($host->getFilteredSortedTableQuery()?->getDQL())->toContain('WHERE song.plays > 15 ORDER BY song.id asc');
});

it('drops the user sort when a default sort closure replaces the query', function (): void {
    $entityManager = seededSongs();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('title')->sortable()])
        ->defaultSort(fn (): QueryBuilder => $entityManager->createQueryBuilder()
            ->select('song')
            ->from(Song::class, 'song')
            ->andWhere('song.plays > 15')));

    $host->sortTable('title');

    $dql = $host->getFilteredSortedTableQuery()?->getDQL() ?? '';

    expect($dql)->toContain('WHERE song.plays > 15 ORDER BY song.id asc')
        ->and($dql)->not->toContain('song.title')
        ->and(array_keys($host->getTableRecords()->getRecords()))->toBe([1, 3]);
});

it('reads the tiebreaker off the builder a default sort closure returned', function (): void {
    $entityManager = seededSongs();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('title')->sortable()])
        ->defaultSort(fn (): QueryBuilder => $entityManager->createQueryBuilder()
            ->select('book')
            ->from(Book::class, 'book')));

    $query = $host->getFilteredSortedTableQuery();

    expect($query?->getDQL())->toContain('ORDER BY book.code asc')
        ->and($query?->getDQL())->not->toContain('song.')
        ->and($query?->getQuery()->getSQL())->toContain('ORDER BY');
});

it('takes the default sort column and the key sort switch from closures', function (): void {
    $entityManager = seededSongs();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('title')->sortable()])
        ->defaultSort(fn (): string => 'plays', 'desc')
        ->defaultKeySort(fn (): bool => false));

    $dql = $host->getFilteredSortedTableQuery()?->getDQL() ?? '';

    expect($dql)->toContain('ORDER BY song.plays desc')
        ->and($dql)->not->toContain('song.id')
        ->and(sortedSongTitles($host))->toBe(['Charlie', 'Bravo', 'Alpha']);
});

it('suppresses the tiebreaker when the key is already ordered', function (): void {
    $entityManager = seededSongs();
    $keyed = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()
            ->select('song')
            ->from(Song::class, 'song')
            ->addOrderBy('song.id', 'DESC')));

    $keyedDql = $keyed->getFilteredSortedTableQuery()?->getDQL() ?? '';

    expect($keyedDql)->toContain('ORDER BY song.id DESC')
        ->and(mb_substr_count($keyedDql, 'song.id'))->toBe(1)
        ->and(sortedSongTitles($keyed))->toBe(['Bravo', 'Alpha', 'Charlie']);
});

it('tiebreaks a composite identifier by every identifier field in mapping order', function (): void {
    $entityManager = EntityManagerFactory::create();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('shortcut')->from(Shortcut::class, 'shortcut'))
        ->columns([TextColumn::make('label')->sortable()]));

    $host->sortTable('label');

    expect($host->getFilteredSortedTableQuery()?->getDQL())
        ->toContain('ORDER BY shortcut.label asc, shortcut.host asc, shortcut.path asc');
});

it('appends only the identifier fields the query does not already order by', function (): void {
    $entityManager = EntityManagerFactory::create();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()
            ->select('shortcut')
            ->from(Shortcut::class, 'shortcut')
            ->addOrderBy('shortcut.path', 'DESC')));

    $dql = $host->getFilteredSortedTableQuery()?->getDQL() ?? '';

    expect($dql)->toContain('ORDER BY shortcut.path DESC, shortcut.host asc')
        ->and(mb_substr_count($dql, 'shortcut.path'))->toBe(1);
});

it('tiebreaks a derived identity by the association path, which orders by its join column', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->query(seededResidenciesByVenue()->createQueryBuilder()->select('residency')->from(Residency::class, 'residency'))
        ->paginated(false));

    $query = $host->getFilteredSortedTableQuery();

    expect($query?->getDQL())->toContain('ORDER BY residency.venue asc, residency.season asc')
        ->and(array_map(static fn (Residency $record): string => $record->getLabel(), $query?->getQuery()->getResult() ?? []))
        ->toBe(['Arena summer', 'Arena winter', 'Shed summer']);
});

it('omits the tiebreaker when defaultKeySort is off', function (): void {
    $entityManager = seededSongs();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('title')->sortable()])
        ->defaultKeySort(false));

    $host->sortTable('title');

    expect($host->getFilteredSortedTableQuery()?->getDQL())->toContain('ORDER BY song.title asc')
        ->and($host->getFilteredSortedTableQuery()?->getDQL())->not->toContain('song.id');
});

it('sorts by a to-one relation both directions keeping null-relation rows', function (): void {
    $entityManager = seededSongsByArtist();
    $configure = fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('artist.name')->sortable()]);
    $ascending = new TableHost($configure);
    $descending = new TableHost($configure);

    $ascending->sortTable('artist.name');
    $descending->sortTable('artist.name', 'desc');

    expect($ascending->getFilteredSortedTableQuery()?->getDQL())
        ->toContain('LEFT JOIN song.artist sy_artist', 'ORDER BY sy_artist.name asc, song.id asc')
        ->and($descending->getFilteredSortedTableQuery()?->getDQL())
        ->toContain('ORDER BY sy_artist.name desc, song.id desc')
        ->and(array_keys($ascending->getTableRecords()->getRecords()))->toBe(match (TestPlatform::current()) {
            TestPlatform::PostgreSql => [2, 1, 3],
            TestPlatform::Sqlite, TestPlatform::MySql => [3, 2, 1],
        })
        ->and($ascending->getTableRecords()->getTotalRecordCount())->toBe(3)
        ->and(array_keys($descending->getTableRecords()->getRecords()))->toBe(match (TestPlatform::current()) {
            TestPlatform::PostgreSql => [3, 1, 2],
            TestPlatform::Sqlite, TestPlatform::MySql => [1, 2, 3],
        });
});

it('applies the identifier tiebreak alongside a relation sort', function (): void {
    $entityManager = EntityManagerFactory::create();
    $first = new Artist(1, 'Alpha');
    $second = new Artist(2, 'Alpha');
    $last = new Artist(3, 'Zeta');

    EntityManagerFactory::seed(
        $entityManager,
        $first,
        $second,
        $last,
        new Song(1, 'Aria', 10, $last),
        new Song(2, 'Bolero', 20, $second),
        new Song(3, 'Cantata', 30, $first),
    );

    $configure = fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('artist.name')->sortable()]);
    $ascending = new TableHost($configure);
    $descending = new TableHost($configure);

    $ascending->sortTable('artist.name');
    $descending->sortTable('artist.name', 'desc');

    expect($ascending->getFilteredSortedTableQuery()?->getDQL())->toContain('ORDER BY sy_artist.name asc, song.id asc')
        ->and(array_keys($ascending->getTableRecords()->getRecords()))->toBe([2, 3, 1])
        ->and(array_keys($descending->getTableRecords()->getRecords()))->toBe([1, 3, 2]);
});

it('still suppresses the tiebreak when the query already orders by the root identifier', function (): void {
    $entityManager = seededSongs();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('title')->sortable()])
        ->defaultSort('id', 'desc'));

    $query = $host->getFilteredSortedTableQuery();

    expect($query?->getDQL())->toContain('ORDER BY song.id desc')
        ->and($query?->getDQLPart('orderBy'))->toHaveCount(1)
        ->and(sortedSongTitles($host))->toBe(['Bravo', 'Alpha', 'Charlie']);
});

it('no longer suppresses the tiebreak for a relation path ending in the identifier field', function (): void {
    $entityManager = seededSongsByArtist();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('artist.id')->sortable()]));
    $joined = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()
            ->select('song')
            ->from(Song::class, 'song')
            ->leftJoin('song.artist', 'artist')
            ->addOrderBy('artist.id', 'ASC')));

    $host->sortTable('artist.id');

    $query = $host->getFilteredSortedTableQuery();

    expect($query?->getDQL())->toContain('ORDER BY sy_artist.id asc, song.id asc')
        ->and($query?->getDQLPart('orderBy'))->toHaveCount(2)
        ->and(array_keys($host->getTableRecords()->getRecords()))->toBe(match (TestPlatform::current()) {
            TestPlatform::PostgreSql => [1, 2, 3],
            TestPlatform::Sqlite, TestPlatform::MySql => [3, 1, 2],
        })
        ->and($joined->getFilteredSortedTableQuery()?->getDQL())->toContain('ORDER BY artist.id ASC, song.id asc');
});

it('resolves a relation path in the default sort', function (): void {
    $entityManager = seededSongsByArtist();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([TextColumn::make('title')->sortable()])
        ->defaultSort('artist.name', 'desc'));

    expect($host->getFilteredSortedTableQuery()?->getDQL())
        ->toContain('LEFT JOIN song.artist sy_artist', 'ORDER BY sy_artist.name desc, song.id desc')
        ->and(array_keys($host->getTableRecords()->getRecords()))->toBe(match (TestPlatform::current()) {
            TestPlatform::PostgreSql => [3, 1, 2],
            TestPlatform::Sqlite, TestPlatform::MySql => [1, 2, 3],
        });
});

it('keeps a user-authored order ahead of table sorts', function (): void {
    $entityManager = seededSongs();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()
            ->select('song')
            ->from(Song::class, 'song')
            ->addOrderBy('song.plays', 'ASC'))
        ->columns([TextColumn::make('title')->sortable()]));

    $host->sortTable('title');

    expect($host->getFilteredSortedTableQuery()?->getDQL())->toContain('ORDER BY song.plays ASC, song.title asc, song.id asc')
        ->and(sortedSongTitles($host))->toBe(['Alpha', 'Bravo', 'Charlie']);
});
