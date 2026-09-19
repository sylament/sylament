<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Sylament\Support\Pagination\Paginator;
use Sylament\Tables\Enums\PaginationMode;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Genre;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Gig;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Locker;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\LockerItem;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\LockerSeal;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;
use Sylament\Tables\Tests\Fixtures\Doctrine\RecordingMiddleware;

/*
 * Covers the query half of host-side pagination — the slice `paginateTableQuery()`
 * takes and the counts it hands back. The `$tablePage` / `$tableRecordsPerPage`
 * state it reads stays in `HasTableTest`.
 */

function pagedSongs(int $count): EntityManager
{
    $entityManager = EntityManagerFactory::create();
    $songs = [];

    foreach (range(1, $count) as $number) {
        $songs[] = new Song($number, "Song {$number}", $number * 10);
    }

    EntityManagerFactory::seed($entityManager, ...$songs);

    return $entityManager;
}

function pagedArtists(): EntityManager
{
    $entityManager = EntityManagerFactory::create();
    $artists = [new Artist(1, 'Alpha'), new Artist(2, 'Bravo'), new Artist(3, 'Charlie')];
    $songs = [];

    foreach ($artists as $index => $artist) {
        foreach (range(1, 3) as $number) {
            $songs[] = new Song($index * 3 + $number, "Song {$number}", $number * 10, $artist);
        }
    }

    EntityManagerFactory::seed($entityManager, ...$artists, ...$songs);

    return $entityManager;
}

/**
 * Two venues with a gig each and four residencies across them — a derived-identity
 * root whose association half reaches a to-many one hop out, which is the only
 * shape Doctrine's fetch-join paginator cannot key.
 */
function pagedResidencies(?RecordingMiddleware $recorder = null): EntityManager
{
    $entityManager = EntityManagerFactory::create($recorder);

    $apollo = new Venue(new Handle('apollo'), 'Apollo');
    $forum = new Venue(new Handle('forum'), 'Forum');

    EntityManagerFactory::seed(
        $entityManager,
        $apollo,
        $forum,
        new Gig(1, 'Opening night', 400, $apollo),
        new Gig(2, 'Closing night', 500, $apollo),
        new Gig(3, 'One night only', 600, $forum),
        pagedResidency($apollo, '2026-spring', 'Apollo spring', 10),
        pagedResidency($apollo, '2026-autumn', 'Apollo autumn', 20),
        pagedResidency($forum, '2026-spring', 'Forum spring', 30),
        pagedResidency($forum, '2026-autumn', 'Forum autumn', 40),
    );

    return $entityManager;
}

function pagedResidency(Venue $venue, string $season, string $label, int $position): Residency
{
    $residency = new Residency();
    $residency->setVenue($venue);
    $residency->setSeason($season);
    $residency->setLabel($label);
    $residency->setPosition($position);

    return $residency;
}

/**
 * Two lockers with a seal each, and items to fetch-join — a root whose whole
 * identifier is one association, so it is keyed without being composite, over a
 * type whose stored spelling is not the one it reads back.
 */
function sealedLockers(): EntityManager
{
    $entityManager = EntityManagerFactory::create();

    $first = new Locker('AB-1', 'First');
    $second = new Locker('CD-2', 'Second');

    EntityManagerFactory::seed(
        $entityManager,
        $first,
        $second,
        new LockerItem(1, 'Boots', 4, $first),
        new LockerItem(2, 'Coat', 6, $first),
        new LockerItem(3, 'Hat', 1, $second),
        new LockerSeal($first, 'First seal', 1),
        new LockerSeal($second, 'Second seal', 2),
    );

    return $entityManager;
}

it('paginates the query lane with a true total and sliced records', function (): void {
    $entityManager = pagedSongs(7);
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->paginated([2, 5]));

    $firstPage = $host->getTableRecords();

    expect($firstPage)->toBeInstanceOf(Paginator::class)
        ->and(array_keys($firstPage->getRecords()))->toBe([1, 2])
        ->and($firstPage->getPage())->toBe(1)
        ->and($firstPage->getRecordsPerPage())->toBe(2)
        ->and($firstPage->getTotalRecordCount())->toBe(7)
        ->and($firstPage->getLastPage())->toBe(4);

    $host->gotoTablePage(3);

    $thirdPage = $host->getTableRecords();

    expect(array_keys($thirdPage->getRecords()))->toBe([5, 6])
        ->and($thirdPage->getPage())->toBe(3)
        ->and($thirdPage->getTotalRecordCount())->toBe(7);
});

it('renders a page past the end empty with the total intact', function (): void {
    $entityManager = pagedSongs(7);
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->paginated([2, 5]));

    $host->gotoTablePage(99);

    $paginator = $host->getTableRecords();

    expect($paginator->getRecords())->toBe([])
        ->and($paginator->getPage())->toBe(99)
        ->and($paginator->getTotalRecordCount())->toBe(7)
        ->and($paginator->getLastPage())->toBe(4);
});

it('shows everything under the all option', function (): void {
    $entityManager = pagedSongs(7);
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->paginated([2, 'all']));

    $host->tableRecordsPerPage = 'all';

    $paginator = $host->getTableRecords();

    expect(array_keys($paginator->getRecords()))->toBe([1, 2, 3, 4, 5, 6, 7])
        ->and($paginator->getRecordsPerPage())->toBe('all')
        ->and($paginator->getTotalRecordCount())->toBe(7)
        ->and($paginator->getLastPage())->toBe(1);
});

it('slices by a numeric-string page size as an integer', function (): void {
    $entityManager = pagedSongs(11);
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->paginated(['10', 25]));

    $paginator = $host->getTableRecords();

    expect($paginator->getRecords())->toHaveCount(10)
        ->and($paginator->getRecordsPerPage())->toBe(10)
        ->and($paginator->getTotalRecordCount())->toBe(11);
});

it('probes one extra record in simple mode instead of counting', function (): void {
    $entityManager = pagedSongs(5);
    $configure = fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->paginated([2, 5])
        ->paginationMode(PaginationMode::Simple);
    $host = new TableHost($configure);
    $lastPageHost = new TableHost($configure);

    $lastPageHost->gotoTablePage(3);

    $firstPage = $host->getTableRecords();
    $lastPage = $lastPageHost->getTableRecords();

    expect(array_keys($firstPage->getRecords()))->toBe([1, 2])
        ->and($firstPage->getTotalRecordCount())->toBeNull()
        ->and($firstPage->getLastPage())->toBeNull()
        ->and($firstPage->hasMorePages())->toBeTrue()
        ->and(array_keys($lastPage->getRecords()))->toBe([5])
        ->and($lastPage->getTotalRecordCount())->toBeNull()
        ->and($lastPage->hasMorePages())->toBeFalse();
});

it('degenerates simple mode to a known total under the all option', function (): void {
    $entityManager = pagedSongs(5);
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->paginated([2, 'all'])
        ->paginationMode(PaginationMode::Simple));

    $host->tableRecordsPerPage = 'all';

    $paginator = $host->getTableRecords();

    expect($paginator->getRecords())->toHaveCount(5)
        ->and($paginator->getRecordsPerPage())->toBe('all')
        ->and($paginator->getTotalRecordCount())->toBe(5)
        ->and($paginator->hasMorePages())->toBeFalse();
});

it('refuses cursor pagination naming the gap', function (): void {
    $entityManager = pagedSongs(3);
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->paginationMode(PaginationMode::Cursor));

    $host->getTableRecords();
})->throws(LogicException::class, 'cursor');

it('paginates entities, not joined rows, under a fetch join', function (): void {
    $entityManager = pagedArtists();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()
            ->select('artist')
            ->from(Artist::class, 'artist')
            ->leftJoin('artist.songs', 'songs')
            ->addSelect('songs'))
        ->paginated([2, 5]));

    $paginator = $host->getTableRecords();
    $records = $paginator->getRecords();

    expect($records)->toHaveCount(2)
        ->and(array_keys($records))->toBe([1, 2])
        ->and($records[1])->toBeInstanceOf(Artist::class)
        ->and($records[1]->getSongs())->toHaveCount(3)
        ->and($paginator->getTotalRecordCount())->toBe(3);
});

it('pages a to-one fetch join as plain rows, the relation still hydrated', function (): void {
    $entityManager = pagedArtists();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()
            ->select('song')
            ->from(Song::class, 'song')
            ->leftJoin('song.artist', 'artist')
            ->addSelect('artist'))
        ->paginated([2, 5]));

    $paginator = $host->getTableRecords();
    $records = $paginator->getRecords();

    expect($records)->toHaveCount(2)
        ->and(array_keys($records))->toBe([1, 2])
        ->and($records[1])->toBeInstanceOf(Song::class)
        ->and($records[1]->getArtist()?->getName())->toBe('Alpha')
        ->and($paginator->getTotalRecordCount())->toBe(9);
});

it('returns a plain keyed array when pagination is off', function (): void {
    $entityManager = pagedSongs(3);
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->paginated(false));

    $records = $host->getTableRecords();

    expect($records)->toBeArray()
        ->and(array_keys($records))->toBe([1, 2, 3])
        ->and($host->getTablePaginator())->toBeNull();
});

/*
 * Doctrine's collection-aware paginator resolves a root's rows through one
 * identifier column, which a key of two fields has no column to be, so a keyed
 * root reads its page the same two steps in its own terms: the page's
 * identifiers, then the records those keys name. What the join buys is what the
 * last assertion reads — every record's collection whole, on a page sliced by
 * records rather than by joined rows.
 */
it('pages a composite table whose query joins a to-many association', function (): void {
    $entityManager = pagedResidencies();
    $configure = fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()
            ->select('residency', 'sy_gig_venue', 'sy_gigs')
            ->from(Residency::class, 'residency')
            ->leftJoin('residency.venue', 'sy_gig_venue')
            ->leftJoin('sy_gig_venue.gigs', 'sy_gigs')
            ->orderBy('residency.position', 'ASC'))
        ->paginated([2]);

    $host = new TableHost($configure);
    $lastPageHost = new TableHost($configure);

    $lastPageHost->gotoTablePage(2);

    $firstPage = $host->getTableRecords();
    $lastPage = $lastPageHost->getTableRecords();

    expect(array_keys($firstPage->getRecords()))->toBe(['apollo~2026-spring', 'apollo~2026-autumn'])
        ->and(array_keys($lastPage->getRecords()))->toBe(['forum~2026-spring', 'forum~2026-autumn'])
        ->and($firstPage->getTotalRecordCount())->toBe(4)
        ->and($firstPage->getLastPage())->toBe(2)
        ->and($firstPage->getRecords()['apollo~2026-spring']->getVenue()?->getGigs())->toHaveCount(2)
        ->and($lastPage->getRecords()['forum~2026-autumn']->getVenue()?->getGigs())->toHaveCount(1);
});

/*
 * The whole identifier is one association here, so `isIdentifierComposite` is
 * false and Doctrine's walkers do serve the root — right up to the identifier
 * tiebreak, which they re-emit as a column their own derived table does not
 * carry. A flavour test written against the composite flag alone leaves this
 * page a driver error on every platform without row numbers.
 */
it('pages a single-derived-identity table whose query joins a to-many association', function (): void {
    $entityManager = sealedLockers();

    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()
            ->select('seal', 'sy_locker', 'sy_items')
            ->from(LockerSeal::class, 'seal')
            ->leftJoin('seal.locker', 'sy_locker')
            ->leftJoin('sy_locker.items', 'sy_items')
            ->orderBy('seal.position', 'ASC'))
        ->paginated([1]));

    $paginator = $host->getTableRecords();

    expect(array_keys($paginator->getRecords()))->toBe(['AB-1'])
        ->and($paginator->getTotalRecordCount())->toBe(2)
        ->and($paginator->getRecords()['AB-1']->getLocker()->getItems())->toHaveCount(2);
});

/*
 * Simple mode probes one record past the page instead of counting, and the probe
 * is a record rather than a joined row here too.
 */
it('probes one extra record past a keyed fetch-joined page', function (): void {
    $entityManager = pagedResidencies();
    $configure = fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()
            ->select('residency', 'sy_gig_venue', 'sy_gigs')
            ->from(Residency::class, 'residency')
            ->leftJoin('residency.venue', 'sy_gig_venue')
            ->leftJoin('sy_gig_venue.gigs', 'sy_gigs')
            ->orderBy('residency.position', 'ASC'))
        ->paginated([3])
        ->paginationMode(PaginationMode::Simple);

    $host = new TableHost($configure);
    $lastPageHost = new TableHost($configure);

    $lastPageHost->gotoTablePage(2);

    $firstPage = $host->getTableRecords();
    $lastPage = $lastPageHost->getTableRecords();

    expect(array_keys($firstPage->getRecords()))->toBe(['apollo~2026-spring', 'apollo~2026-autumn', 'forum~2026-spring'])
        ->and($firstPage->getTotalRecordCount())->toBeNull()
        ->and($firstPage->hasMorePages())->toBeTrue()
        ->and(array_keys($lastPage->getRecords()))->toBe(['forum~2026-autumn'])
        ->and($lastPage->hasMorePages())->toBeFalse();
});

/*
 * The keyed read is the keyed root's alone: a single-identifier fetch-joined page
 * keeps narrowing through Doctrine's own `WhereInWalker`, one `IN` over one
 * column, where a keyed one narrows by the per-field disjunction the codec binds.
 * Both pages read three statements — the identifiers, the records, the total.
 */
it('narrows a single-identifier fetch-joined page through Doctrine, and a keyed one through the codec', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = pagedResidencies($recorder);

    $singleIdentifier = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()
            ->select('venue', 'sy_gigs')
            ->from(Venue::class, 'venue')
            ->leftJoin('venue.gigs', 'sy_gigs')
            ->orderBy('venue.name', 'ASC'))
        ->paginated([1]));

    $recorder->reset();
    $singleIdentifier->getTableRecords();

    $doctrineStatements = $recorder->statements();

    $keyed = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()
            ->select('residency', 'sy_gig_venue', 'sy_gigs')
            ->from(Residency::class, 'residency')
            ->leftJoin('residency.venue', 'sy_gig_venue')
            ->leftJoin('sy_gig_venue.gigs', 'sy_gigs')
            ->orderBy('residency.position', 'ASC'))
        ->paginated([1]));

    $recorder->reset();
    $keyed->getTableRecords();

    $keyedStatements = $recorder->statements();

    expect($doctrineStatements)->toHaveCount(3)
        ->and($doctrineStatements[1])->toContain('.handle IN (?)')
        ->and($keyedStatements)->toHaveCount(3)
        ->and($keyedStatements[1])->toContain('.venue_id = ? AND')
        ->and($keyedStatements[1])->not->toContain('IN (?)');
});

/*
 * A table that never pages never meets either read: an unpaginated one returns
 * before `paginateTableQuery()`, and `recordsPerPage: 'all'` returns from inside
 * it before any window is taken.
 */
it('renders a composite table joining a to-many when it is not paged', function (string $option): void {
    $entityManager = pagedResidencies();
    $host = new TableHost(function (Table $table) use ($entityManager, $option): Table {
        $table->query($entityManager->createQueryBuilder()
            ->select('residency', 'sy_gig_venue', 'sy_gigs')
            ->from(Residency::class, 'residency')
            ->leftJoin('residency.venue', 'sy_gig_venue')
            ->leftJoin('sy_gig_venue.gigs', 'sy_gigs')
            ->orderBy('residency.position', 'ASC'));

        return $option === 'all' ? $table->paginated([2, 'all']) : $table->paginated(false);
    });

    if ($option === 'all') {
        $host->tableRecordsPerPage = 'all';
    }

    $records = $host->getTableRecords();

    expect(array_keys($records instanceof Paginator ? $records->getRecords() : $records))
        ->toBe(['apollo~2026-spring', 'apollo~2026-autumn', 'forum~2026-spring', 'forum~2026-autumn']);
})->with(['paginated(false)' => ['none'], "recordsPerPage 'all'" => ['all']]);

it('pages a composite table whose query joins no collection', function (): void {
    $entityManager = pagedResidencies();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('residency')->from(Residency::class, 'residency')->orderBy('residency.position', 'ASC'))
        ->paginated([2]));

    $paginator = $host->getTableRecords();

    expect($paginator)->toBeInstanceOf(Paginator::class)
        ->and(array_keys($paginator->getRecords()))->toBe(['apollo~2026-spring', 'apollo~2026-autumn'])
        ->and($paginator->getTotalRecordCount())->toBe(4)
        ->and($paginator->getLastPage())->toBe(2);
});

/*
 * The refusal is the composite arm only: a single-identifier root fetch-joining a
 * to-many is what Doctrine's paginator was written for, and it still pages records
 * rather than joined rows.
 */
it('leaves a single-identifier table fetch-joining a to-many free to page', function (): void {
    $entityManager = EntityManagerFactory::create();

    $radiohead = new Artist(1, 'Radiohead');
    $rock = new Genre(1, 'Rock');
    $tripHop = new Genre(2, 'Trip hop');
    $airbag = new Song(id: 1, title: 'Airbag', plays: 10, artist: $radiohead);

    $airbag->getGenres()->add($rock);
    $airbag->getGenres()->add($tripHop);

    EntityManagerFactory::seed(
        $entityManager,
        $radiohead,
        $rock,
        $tripHop,
        $airbag,
        new Song(id: 2, title: 'Let Down', plays: 20, artist: $radiohead),
        new Song(id: 3, title: 'No Surprises', plays: 30, artist: $radiohead),
    );

    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()
            ->select('song', 'sy_genres')
            ->from(Song::class, 'song')
            ->leftJoin('song.genres', 'sy_genres')
            ->orderBy('song.id', 'ASC'))
        ->paginated([2]));

    $paginator = $host->getTableRecords();

    expect(array_keys($paginator->getRecords()))->toBe([1, 2])
        ->and($paginator->getTotalRecordCount())->toBe(3);
});
