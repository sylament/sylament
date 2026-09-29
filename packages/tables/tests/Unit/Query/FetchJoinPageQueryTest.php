<?php

declare(strict_types=1);

use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker;
use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Sylament\Support\Tests\Doctrine\TestPlatform;
use Sylament\Tables\Query\FetchJoinPageQuery;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Gig;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Locker;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\LockerItem;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\LockerLease;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\LockerLeaseNote;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\LockerSeal;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\ResidencyShow;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\ShortcutHit;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;

/*
 * The two-step window a keyed root pages through: the page's identifiers off
 * Doctrine's own limit walker, then the records off the same builder unwindowed
 * and narrowed to those keys. Every query here carries the identifier tiebreak
 * `CanSortRecords` appends to a real page query, because that item is the one
 * the first step cannot walk unrewritten.
 */

/**
 * Four flavours of keyed root, each with a collection to fetch-join, over one
 * rig: a scalar pair with its own to-many and a key needing every escape the
 * codec has, a derived identity with both an own to-many and a chained one
 * through its identifier association, a single derived identity whose target is
 * keyed by a type that stores one spelling and reads back another, and a join
 * entity keyed by two associations at once — the only shape whose tiebreak
 * rewrites two order items on the same page.
 */
function fetchJoinPageRig(?RecordingMiddleware $recorder = null): EntityManager
{
    $entityManager = EntityManagerFactory::create($recorder);

    $apollo = new Venue(new Handle('apollo'), 'Apollo');
    $forum = new Venue(new Handle('forum'), 'Forum');

    $apolloSpring = fetchJoinPageResidency($apollo, '2026-spring', 10);
    $apolloAutumn = fetchJoinPageResidency($apollo, '2026-autumn', 20);

    $home = fetchJoinPageShortcut('example.org', '/home', 1);
    $docs = fetchJoinPageShortcut('sylament.test', '/docs/a b', 2);

    $first = new Locker('AB-1', 'First');
    $second = new Locker('CD-2', 'Second');

    $alpha = new Artist(1, 'Alpha');
    $bravo = new Artist(2, 'Bravo');

    $firstAlpha = fetchJoinPageLease($first, $alpha, 10);
    $firstBravo = fetchJoinPageLease($first, $bravo, 20);
    $secondBravo = fetchJoinPageLease($second, $bravo, 40);

    EntityManagerFactory::seed(
        $entityManager,
        $apollo,
        $forum,
        new Gig(1, 'Opening night', 400, $apollo),
        new Gig(2, 'Closing night', 500, $apollo),
        new Gig(3, 'One night only', 600, $forum),
        $apolloSpring,
        $apolloAutumn,
        fetchJoinPageResidency($forum, '2026-spring', 30),
        fetchJoinPageResidency($forum, '2026-autumn', 40),
        new ResidencyShow(1, 'Night one', 120, $apolloSpring),
        new ResidencyShow(2, 'Night two', 130, $apolloSpring),
        new ResidencyShow(3, 'Night three', 140, $apolloAutumn),
        $home,
        $docs,
        fetchJoinPageShortcut('example.org', '/about', 3),
        new ShortcutHit(1, 'Mozilla/5.0', 5, $home),
        new ShortcutHit(2, 'Safari/18', 25, $docs),
        new ShortcutHit(3, 'Lynx/2.9', 7, $docs),
        $first,
        $second,
        new LockerItem(1, 'Boots', 4, $first),
        new LockerItem(2, 'Coat', 6, $first),
        new LockerItem(3, 'Hat', 1, $second),
        new LockerSeal($first, 'First seal', 1),
        new LockerSeal($second, 'Second seal', 2),
        $alpha,
        $bravo,
        $firstAlpha,
        $firstBravo,
        fetchJoinPageLease($second, $alpha, 30),
        $secondBravo,
        new LockerLeaseNote(1, 'Key handover', 1, $firstAlpha),
        new LockerLeaseNote(2, 'Damp patch', 2, $firstAlpha),
        new LockerLeaseNote(3, 'Lock replaced', 1, $firstBravo),
        new LockerLeaseNote(4, 'Renewal due', 1, $secondBravo),
        new LockerLeaseNote(5, 'Deposit held', 2, $secondBravo),
        new LockerLeaseNote(6, 'Spare key cut', 3, $secondBravo),
    );

    return $entityManager;
}

function fetchJoinPageResidency(Venue $venue, string $season, int $position): Residency
{
    $residency = new Residency();
    $residency->setVenue($venue);
    $residency->setSeason($season);
    $residency->setLabel("{$venue->getName()} {$season}");
    $residency->setPosition($position);

    return $residency;
}

function fetchJoinPageShortcut(string $host, string $path, int $position): Shortcut
{
    $shortcut = new Shortcut();
    $shortcut->setHost($host);
    $shortcut->setPath($path);
    $shortcut->setLabel("{$host}{$path}");
    $shortcut->setPosition($position);

    return $shortcut;
}

function fetchJoinPageLease(Locker $locker, Artist $artist, int $position): LockerLease
{
    $lease = new LockerLease();
    $lease->setLocker($locker);
    $lease->setArtist($artist);
    $lease->setLabel("{$artist->getName()} at {$locker->getCode()}");
    $lease->setPosition($position);

    return $lease;
}

function fetchJoinPageShortcutQuery(EntityManager $entityManager): QueryBuilder
{
    $query = $entityManager->createQueryBuilder()
        ->select('shortcut', 'sy_hits')
        ->from(Shortcut::class, 'shortcut')
        ->leftJoin('shortcut.hits', 'sy_hits')
        ->orderBy('shortcut.position', 'ASC');

    IdentifierConstraint::orderBy($query, 'shortcut', Shortcut::class, 'asc');

    return $query;
}

function fetchJoinPageResidencyQuery(EntityManager $entityManager): QueryBuilder
{
    $query = $entityManager->createQueryBuilder()
        ->select('residency', 'sy_shows')
        ->from(Residency::class, 'residency')
        ->leftJoin('residency.shows', 'sy_shows')
        ->orderBy('residency.position', 'ASC');

    IdentifierConstraint::orderBy($query, 'residency', Residency::class, 'asc');

    return $query;
}

function fetchJoinPageSealQuery(EntityManager $entityManager): QueryBuilder
{
    $query = $entityManager->createQueryBuilder()
        ->select('seal', 'sy_locker', 'sy_items')
        ->from(LockerSeal::class, 'seal')
        ->leftJoin('seal.locker', 'sy_locker')
        ->leftJoin('sy_locker.items', 'sy_items')
        ->orderBy('seal.position', 'ASC');

    IdentifierConstraint::orderBy($query, 'seal', LockerSeal::class, 'asc');

    return $query;
}

function fetchJoinPageGigQuery(EntityManager $entityManager): QueryBuilder
{
    return $entityManager->createQueryBuilder()
        ->select('gig')
        ->from(Gig::class, 'gig')
        ->orderBy('gig.id', 'ASC');
}

function fetchJoinPageLeaseQuery(EntityManager $entityManager): QueryBuilder
{
    $query = $entityManager->createQueryBuilder()
        ->select('lease', 'sy_notes')
        ->from(LockerLease::class, 'lease')
        ->leftJoin('lease.notes', 'sy_notes')
        ->orderBy('lease.position', 'ASC');

    IdentifierConstraint::orderBy($query, 'lease', LockerLease::class, 'asc');

    return $query;
}

/**
 * @param list<object|array<string, mixed>> $records
 *
 * @return list<string>
 */
function fetchJoinPageKeys(EntityManager $entityManager, array $records): array
{
    return array_map(
        static fn (object|array $record): string => is_object($record) ? RecordKey::of($entityManager, $record) : '',
        $records,
    );
}

it('pages a scalar-pair root over its own to-many with the collections whole', function (): void {
    $entityManager = fetchJoinPageRig();

    $records = FetchJoinPageQuery::read(fetchJoinPageShortcutQuery($entityManager), Shortcut::class, 0, 2);

    expect(fetchJoinPageKeys($entityManager, $records))->toBe(['example=2Eorg~=2Fhome', 'sylament=2Etest~=2Fdocs=2Fa=20b'])
        ->and($records[0])->toBeInstanceOf(Shortcut::class)
        ->and($records[0]->getHits())->toHaveCount(1)
        ->and($records[1]->getHits())->toHaveCount(2);
});

it('pages a derived identity over its own to-many, in the page query own order', function (): void {
    $entityManager = fetchJoinPageRig();

    $firstPage = FetchJoinPageQuery::read(fetchJoinPageResidencyQuery($entityManager), Residency::class, 0, 2);
    $secondPage = FetchJoinPageQuery::read(fetchJoinPageResidencyQuery($entityManager), Residency::class, 2, 2);

    expect(fetchJoinPageKeys($entityManager, $firstPage))->toBe(['apollo~2026-spring', 'apollo~2026-autumn'])
        ->and(fetchJoinPageKeys($entityManager, $secondPage))->toBe(['forum~2026-spring', 'forum~2026-autumn'])
        ->and($firstPage[0]->getShows())->toHaveCount(2)
        ->and($firstPage[1]->getShows())->toHaveCount(1)
        ->and($secondPage[0]->getShows())->toHaveCount(0);
});

/*
 * The collection one hop out through the identifier association itself, which is
 * where the rewrite has to hold for a path it did not join.
 */
it('pages a derived identity across a collection reached through its identifier association', function (): void {
    $entityManager = fetchJoinPageRig();

    $query = $entityManager->createQueryBuilder()
        ->select('residency', 'sy_venue', 'sy_gigs')
        ->from(Residency::class, 'residency')
        ->leftJoin('residency.venue', 'sy_venue')
        ->leftJoin('sy_venue.gigs', 'sy_gigs')
        ->orderBy('residency.position', 'ASC');

    IdentifierConstraint::orderBy($query, 'residency', Residency::class, 'asc');

    $records = FetchJoinPageQuery::read($query, Residency::class, 0, 3);

    expect(fetchJoinPageKeys($entityManager, $records))->toBe(['apollo~2026-spring', 'apollo~2026-autumn', 'forum~2026-spring'])
        ->and($records[0]->getVenue()?->getGigs())->toHaveCount(2)
        ->and($records[2]->getVenue()?->getGigs())->toHaveCount(1);
});

/*
 * The whole identifier is one association, and its target's type stores `ab-1`
 * where the record reads `AB-1` — so a key printed off the raw walker row decodes
 * to nothing, the narrowing renders `1 = 0`, and the page comes back empty with no
 * error anywhere. Only a converting row read answers these two records.
 */
it('pages a single derived identity through the converting type of its target', function (): void {
    $entityManager = fetchJoinPageRig();

    $records = FetchJoinPageQuery::read(fetchJoinPageSealQuery($entityManager), LockerSeal::class, 0, 2);

    expect(fetchJoinPageKeys($entityManager, $records))->toBe(['AB-1', 'CD-2'])
        ->and($records[0]->getLocker()->getItems())->toHaveCount(2)
        ->and($records[1]->getLocker()->getItems())->toHaveCount(1);
});

/*
 * Both halves of the identifier are associations, so the tiebreak hands the
 * rewrite two order items on one page and each needs a result variable of its
 * own — a single alias for both is a semantical error rather than a wrong page.
 * One half also converts, so the two keys a window answers are only equal to the
 * records' own where the row read converted them back.
 */
it('pages a join entity keyed by two associations, rewriting both order items', function (): void {
    $entityManager = fetchJoinPageRig();

    $firstPage = FetchJoinPageQuery::read(fetchJoinPageLeaseQuery($entityManager), LockerLease::class, 0, 2);
    $secondPage = FetchJoinPageQuery::read(fetchJoinPageLeaseQuery($entityManager), LockerLease::class, 2, 2);

    expect(fetchJoinPageKeys($entityManager, $firstPage))->toBe(['AB-1~1', 'AB-1~2'])
        ->and(fetchJoinPageKeys($entityManager, $secondPage))->toBe(['CD-2~1', 'CD-2~2'])
        ->and($firstPage[0]->getNotes())->toHaveCount(2)
        ->and($firstPage[1]->getNotes())->toHaveCount(1)
        ->and($secondPage[0]->getNotes())->toHaveCount(0)
        ->and($secondPage[1]->getNotes())->toHaveCount(3);
});

/*
 * The regression pin for the whole read. `LimitSubqueryOutputWalker` rewrites an
 * order item from the field mappings alone, so off the `ROW_NUMBER` platforms it
 * re-emits our own identifier tiebreak's `ORDER BY residency.venue` as a column
 * its derived table does not carry. Both halves are asserted, because the
 * walker's rewrite matters on SQLite, MySQL and MariaDB, where one that quietly
 * stops firing leaves a driver error on every page, while PostgreSQL's
 * `ROW_NUMBER` path never needed it.
 */
it('survives the identifier tiebreak a platform without row numbers cannot walk', function (): void {
    $entityManager = fetchJoinPageRig();
    $unrewritten = fetchJoinPageResidencyQuery($entityManager)
        ->getQuery()
        ->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class)
        ->setFirstResult(0)
        ->setMaxResults(2);

    $unrewrittenRead = static fn (): array => $unrewritten->getScalarResult();

    match (TestPlatform::current()) {
        TestPlatform::PostgreSql => expect($unrewrittenRead())->toBeArray(),
        TestPlatform::Sqlite, TestPlatform::MySql => expect($unrewrittenRead)->toThrow(DriverException::class, 'venue_id'),
    };

    expect(fetchJoinPageKeys($entityManager, FetchJoinPageQuery::read(fetchJoinPageResidencyQuery($entityManager), Residency::class, 0, 2)))
        ->toBe(['apollo~2026-spring', 'apollo~2026-autumn']);
});

it('orders a page by a user sort on the identifier association, both ways', function (string $direction, array $keys): void {
    $entityManager = fetchJoinPageRig();

    $query = $entityManager->createQueryBuilder()
        ->select('residency', 'sy_shows')
        ->from(Residency::class, 'residency')
        ->leftJoin('residency.shows', 'sy_shows')
        ->orderBy('residency.venue', $direction);

    IdentifierConstraint::orderBy($query, 'residency', Residency::class, 'asc');

    expect(fetchJoinPageKeys($entityManager, FetchJoinPageQuery::read($query, Residency::class, 0, 2)))->toBe($keys);
})->with([
    'ascending' => ['ASC', ['apollo~2026-autumn', 'apollo~2026-spring']],
    'descending' => ['DESC', ['forum~2026-autumn', 'forum~2026-spring']],
]);

/*
 * An aggregate sort rides the page query as a correlated subselect chosen
 * `AS HIDDEN`, which the walker un-hides before it walks — so the hidden select
 * the rewrite adds is the second one on the same builder, and the two must not
 * collide. The subselect is written out here rather than built, so this case
 * pins the read against the shape rather than against another lane's file.
 */
it('pages under a hidden aggregate sort already on the builder', function (): void {
    $entityManager = fetchJoinPageRig();

    $query = $entityManager->createQueryBuilder()
        ->select('residency', 'sy_shows')
        ->from(Residency::class, 'residency')
        ->leftJoin('residency.shows', 'sy_shows')
        ->addSelect(sprintf(
            '(SELECT COUNT(sy_ag0_0) FROM %s sy_ag0 JOIN sy_ag0.shows sy_ag0_0 WHERE sy_ag0.venue = residency.venue AND sy_ag0.season = residency.season) AS HIDDEN sy_agg0',
            Residency::class,
        ))
        ->orderBy('sy_agg0', 'DESC');

    IdentifierConstraint::orderBy($query, 'residency', Residency::class, 'asc');

    expect(fetchJoinPageKeys($entityManager, FetchJoinPageQuery::read($query, Residency::class, 0, 2)))
        ->toBe(['apollo~2026-spring', 'apollo~2026-autumn']);
});

it('answers an empty page past the end without a second statement', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = fetchJoinPageRig($recorder);
    $query = fetchJoinPageResidencyQuery($entityManager);

    $recorder->reset();

    expect(FetchJoinPageQuery::read($query, Residency::class, 99, 2))->toBe([])
        ->and($recorder->statements())->toHaveCount(1);
});

it('answers an empty table without a second statement', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);
    $query = fetchJoinPageResidencyQuery($entityManager);

    $recorder->reset();

    expect(FetchJoinPageQuery::read($query, Residency::class, 0, 2))->toBe([])
        ->and($recorder->statements())->toHaveCount(1);
});

/*
 * And what each of the two is: the first the limit walker's own derived table,
 * the second the whole builder narrowed to the keys it answered — unwindowed,
 * and ordered by the columns the caller wrote rather than by the result
 * variable the identifier step ordered itself by.
 */
it('costs two statements for a page it answers', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = fetchJoinPageRig($recorder);
    $query = fetchJoinPageResidencyQuery($entityManager);

    $recorder->reset();

    $records = FetchJoinPageQuery::read($query, Residency::class, 0, 2);

    expect($records)->toHaveCount(2)
        ->and($recorder->statements())->toHaveCount(2)
        ->and($recorder->statements()[0])->toContain('dctrn_result')
        ->and($recorder->statements()[1])->not->toContain('LIMIT')
        ->and($recorder->normalizedStatements()[1])->toEndWith('ORDER BY a0_.position ASC, a0_.venue_id ASC, a0_.season ASC')
        ->and($recorder->statements()[1])->not->toContain('sclr_');
});

/*
 * The window is the arguments', whatever the builder carries: the identifier step
 * overrides it by construction, and the record step has to clear it, or the keys
 * of one page would be read back through the offset of another and the page would
 * come up short.
 */
it('reads the window its arguments name rather than one the builder already carries', function (): void {
    $entityManager = fetchJoinPageRig();
    $query = fetchJoinPageResidencyQuery($entityManager)->setFirstResult(3)->setMaxResults(1);

    expect(fetchJoinPageKeys($entityManager, FetchJoinPageQuery::read($query, Residency::class, 0, 2)))
        ->toBe(['apollo~2026-spring', 'apollo~2026-autumn']);
});

/*
 * `INDEX BY` keys what the record step hydrates, and the contract here is a list —
 * the one cross-trait caller reads `[0]` off it, and a string-keyed array answers
 * null there with a record in hand, which prints a trailing group summary for a
 * group that continues. Doctrine own route normalises for the same reason.
 */
it('answers a list whatever the root is indexed by', function (): void {
    $entityManager = fetchJoinPageRig();

    $query = $entityManager->createQueryBuilder()
        ->select('residency', 'sy_shows')
        ->from(Residency::class, 'residency', 'residency.label')
        ->leftJoin('residency.shows', 'sy_shows')
        ->orderBy('residency.position', 'ASC');

    IdentifierConstraint::orderBy($query, 'residency', Residency::class, 'asc');

    $records = FetchJoinPageQuery::read($query, Residency::class, 0, 1);

    expect($records)->toHaveCount(1)
        ->and(array_is_list($records))->toBeTrue()
        ->and($records[0] ?? null)->toBeInstanceOf(Residency::class);
});

/*
 * The caller keeps its builder: a window, a narrowing or a rewritten ordering
 * leaking back would make a second read of the same page a different query.
 */
it('leaves the builder it was handed untouched', function (): void {
    $entityManager = fetchJoinPageRig();
    $query = fetchJoinPageResidencyQuery($entityManager);
    $dql = $query->getDQL();

    FetchJoinPageQuery::read($query, Residency::class, 0, 2);

    expect($query->getDQL())->toBe($dql)
        ->and($query->getFirstResult())->toBe(0)
        ->and($query->getMaxResults())->toBeNull()
        ->and($query->getParameters()->toArray())->toBe([]);
});

/*
 * Doctrine names the walker's key columns itself, so the remap is positional and
 * only the width says the two still agree. A row of another width means the
 * walker no longer answers the shape this read remaps, which is a drift to fail
 * on rather than a page to key by guesswork. Reached here from both sides — a
 * two-column root keyed against a one-field identifier, and a one-column root
 * against a two-field one — which is what such a drift would look like from
 * inside the remap.
 */
it('refuses a walker row whose width is not the identifier own', function (string $width): void {
    $entityManager = fetchJoinPageRig();

    [$query, $recordClass, $message] = $width === 'wider'
        ? [fetchJoinPageShortcutQuery($entityManager), Song::class, 'answered a row of 2 value(s) where the 1 of its identifier were selected']
        : [fetchJoinPageGigQuery($entityManager), Shortcut::class, 'answered a row of 1 value(s) where the 2 of its identifier were selected'];

    expect(fn (): array => FetchJoinPageQuery::read($query, $recordClass, 0, 2))->toThrow(LogicException::class, $message);
})->with(['wider than the identifier' => ['wider'], 'narrower than the identifier' => ['narrower']]);

/*
 * The rewrite leaves an order item over a to-one whose target is keyed by more
 * than one column exactly where it found it, and Doctrine refuses that path
 * expression in any statement it walks. The window here holds no rows, which is
 * the one case the two readings come apart in: the refusal has to stand in
 * front of the identifier statement rather than fall out of the records the
 * page happens to carry.
 */
it('refuses an order item over a two-join-column association on an empty window too', function (): void {
    $entityManager = fetchJoinPageRig();

    $query = $entityManager->createQueryBuilder()
        ->select('sy_show')
        ->from(ResidencyShow::class, 'sy_show')
        ->orderBy('sy_show.residency', 'ASC');

    FetchJoinPageQuery::read($query, ResidencyShow::class, 99, 2);
})->throws(QueryException::class, 'composite primary key');

it('refuses a query naming no root alias', function (): void {
    $entityManager = fetchJoinPageRig();

    FetchJoinPageQuery::read($entityManager->createQueryBuilder()->select('1'), Residency::class, 0, 2);
})->throws(LogicException::class, 'no root alias');
