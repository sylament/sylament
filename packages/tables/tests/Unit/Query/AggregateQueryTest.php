<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tables\Query\AggregateDeclaration;
use Sylament\Tables\Query\AggregateQuery;
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
 * Radiohead with two songs, Adele with none, Björk with one — so a batch over
 * all three exercises a multi-row group, an absent key and a single-row group.
 */
function aggregateFixtureEntityManager(?RecordingMiddleware $recorder = null): EntityManager
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

function aggregateDeclaration(string $function, string $relationshipPath, ?string $column = null, ?Closure $scope = null): AggregateDeclaration
{
    return new AggregateDeclaration(
        function: $function,
        relationshipPath: $relationshipPath,
        column: $column,
        scope: $scope,
        castToBool: false,
    );
}

function aggregateShortcut(string $host, string $path): Shortcut
{
    $shortcut = new Shortcut();
    $shortcut->setHost($host);
    $shortcut->setPath($path);
    $shortcut->setLabel($path);

    return $shortcut;
}

/**
 * A scalar pair whose first key carries a dot, a slash and a space, so a batch
 * keyed without escaping them answers under a key no cell asks for. Two hits on
 * the escaped one, one on the plain one, none on the third.
 */
function aggregateShortcuts(?RecordingMiddleware $recorder = null): EntityManager
{
    $entityManager = EntityManagerFactory::create($recorder);

    $escaped = aggregateShortcut('www.example.com', '/a b');
    $plain = aggregateShortcut('plain', 'about');
    $empty = aggregateShortcut('nohits', 'x');

    EntityManagerFactory::seed(
        $entityManager,
        $escaped,
        $plain,
        $empty,
        new ShortcutHit(1, 'curl', 3, $escaped),
        new ShortcutHit(2, 'wget', 4, $escaped),
        new ShortcutHit(3, 'curl', 10, $plain),
    );

    return $entityManager;
}

function aggregateResidency(Venue $venue, string $season): Residency
{
    $residency = new Residency();
    $residency->setVenue($venue);
    $residency->setSeason($season);
    $residency->setLabel($season);

    return $residency;
}

/**
 * A derived identity whose association component is itself object-keyed, with a
 * to-many of its own (`shows`) and one reachable through the association
 * (`venue.gigs`) — the two hops that read the identifier differently.
 */
function aggregateResidencies(): EntityManager
{
    $entityManager = EntityManagerFactory::create();
    $arena = new Venue(new Handle('arena'), 'The Arena');
    $shed = new Venue(new Handle('shed'), 'The Shed');
    $autumn = aggregateResidency($arena, 'autumn/2026');
    $winter = aggregateResidency($shed, 'winter');
    $empty = aggregateResidency($arena, 'empty');

    EntityManagerFactory::seed(
        $entityManager,
        $arena,
        $shed,
        $autumn,
        $winter,
        $empty,
        new Gig(1, 'Opening', 400, $arena),
        new Gig(2, 'Encore', 900, $arena),
        new Gig(3, 'Matinee', 50, $shed),
        new ResidencyShow(1, 'Night one', 120, $autumn),
        new ResidencyShow(2, 'Night two', 180, $autumn),
        new ResidencyShow(3, 'Matinee', 60, $winter),
    );

    return $entityManager;
}

/**
 * A single derived identity — one `#[Id]` association, so the class is keyed
 * but not composite — onto a target whose type stores `ab-1` and reads back
 * `AB-1`. The two spellings are what tells a converted row read apart from a
 * cast one.
 */
function aggregateLockers(): EntityManager
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
 * `$count` shortcuts under one host, hits only on the first and the last, so
 * the answered keys sit on either side of the 500-key chunk boundary a
 * two-field identifier splits at. Two hits each, one of them under the weight
 * a scope can filter on.
 *
 * @return array{EntityManager, RecordingMiddleware, list<string>}
 */
function aggregateChunkedShortcuts(int $count): array
{
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);
    $shortcuts = [];
    $keys = [];

    for ($index = 1; $index <= $count; $index++) {
        $shortcuts[] = aggregateShortcut('bulk.test', '/p/' . $index);
        $keys[] = 'bulk=2Etest~=2Fp=2F' . $index;
    }

    EntityManagerFactory::seed(
        $entityManager,
        ...$shortcuts,
        ...[
            new ShortcutHit(1, 'curl', 1, $shortcuts[0]),
            new ShortcutHit(2, 'wget', 9, $shortcuts[0]),
            new ShortcutHit(3, 'curl', 2, $shortcuts[$count - 1]),
            new ShortcutHit(4, 'wget', 8, $shortcuts[$count - 1]),
        ],
    );

    $recorder->reset();

    return [$entityManager, $recorder, $keys];
}

it('counts a to-many relation grouped by record key', function (): void {
    $values = AggregateQuery::fetch(
        aggregateFixtureEntityManager(),
        Artist::class,
        aggregateDeclaration('COUNT', 'songs'),
        ['1', '2', '3'],
    );

    expect($values)->toBe([1 => 2, 3 => 1]);
});

it('averages a related column', function (): void {
    $values = AggregateQuery::fetch(
        aggregateFixtureEntityManager(),
        Artist::class,
        aggregateDeclaration('AVG', 'songs', 'plays'),
        ['1', '2', '3'],
    );

    expect($values)->toBe([1 => 15.0, 3 => 300.0]);
});

it('applies the scope closure to the batch builder', function (): void {
    $scope = static fn (QueryBuilder $query, string $alias): QueryBuilder => $query->andWhere("{$alias}.plays > 100");

    $values = AggregateQuery::fetch(
        aggregateFixtureEntityManager(),
        Artist::class,
        aggregateDeclaration('COUNT', 'songs', scope: $scope),
        ['1', '2', '3'],
    );

    expect($values)->toBe([3 => 1]);
});

it('keeps the key narrowing when a scope replaces the where clause', function (): void {
    $scope = static fn (QueryBuilder $query, string $alias): QueryBuilder => $query->where("{$alias}.plays > 15");

    $values = AggregateQuery::fetch(
        aggregateFixtureEntityManager(),
        Artist::class,
        aggregateDeclaration('COUNT', 'songs', scope: $scope),
        ['1'],
    );

    expect($values)->toBe([1 => 1]);
});

it('walks a dotted relationship path', function (): void {
    $values = AggregateQuery::fetch(
        aggregateFixtureEntityManager(),
        Song::class,
        aggregateDeclaration('COUNT', 'artist.songs'),
        ['1', '2', '3'],
    );

    expect($values)->toBe([1 => 2, 2 => 2, 3 => 1]);
});

it('keys a batch by a stringable identifier', function (): void {
    $entityManager = EntityManagerFactory::create();
    $arena = new Venue(new Handle('arena'), 'The Arena');
    $shed = new Venue(new Handle('shed'), 'The Shed');

    EntityManagerFactory::seed(
        $entityManager,
        $arena,
        $shed,
        new Gig(1, 'Opening', 400, $arena),
        new Gig(2, 'Encore', 900, $arena),
        new Gig(3, 'Matinee', 50, $shed),
    );

    $values = AggregateQuery::fetch(
        $entityManager,
        Venue::class,
        aggregateDeclaration('COUNT', 'gigs'),
        ['arena', 'shed'],
    );

    expect($values)->toBe(['arena' => 2, 'shed' => 1]);
});

it('keys a batch over a composite-identifier root by the escaped record key', function (): void {
    $entityManager = aggregateShortcuts();

    $values = AggregateQuery::fetch(
        $entityManager,
        Shortcut::class,
        aggregateDeclaration('COUNT', 'hits'),
        ['www=2Eexample=2Ecom~=2Fa=20b', 'plain~about', 'nohits~x'],
    );

    $recordSideKeys = array_map(
        static fn (Shortcut $shortcut): string => RecordKey::of($entityManager, $shortcut, Shortcut::class),
        $entityManager->getRepository(Shortcut::class)->findBy([], ['host' => 'ASC']),
    );

    expect($values)->toBe(['plain~about' => 1, 'www=2Eexample=2Ecom~=2Fa=20b' => 2])
        ->and($recordSideKeys)->toBe(['nohits~x', 'plain~about', 'www=2Eexample=2Ecom~=2Fa=20b'])
        ->and(array_diff(array_keys($values), $recordSideKeys))->toBe([]);
});

it('sums a related column over a composite-identifier root', function (): void {
    $values = AggregateQuery::fetch(
        aggregateShortcuts(),
        Shortcut::class,
        aggregateDeclaration('SUM', 'hits', 'visits'),
        ['www=2Eexample=2Ecom~=2Fa=20b', 'plain~about', 'nohits~x'],
    );

    expect($values)->toBe(['plain~about' => 10, 'www=2Eexample=2Ecom~=2Fa=20b' => 7]);
});

it('drops a malformed key from a composite batch rather than throwing', function (): void {
    $values = AggregateQuery::fetch(
        aggregateShortcuts(),
        Shortcut::class,
        aggregateDeclaration('COUNT', 'hits'),
        ['plain~about', 'garbage', 'one~two~three'],
    );

    expect($values)->toBe(['plain~about' => 1]);
});

it('keys a batch over a derived identity by its association component', function (): void {
    $entityManager = aggregateResidencies();

    $values = AggregateQuery::fetch(
        $entityManager,
        Residency::class,
        aggregateDeclaration('COUNT', 'shows'),
        ['arena~autumn=2F2026', 'shed~winter', 'arena~empty'],
    );

    $recordSideKeys = array_map(
        static fn (Residency $residency): string => RecordKey::of($entityManager, $residency, Residency::class),
        $entityManager->getRepository(Residency::class)->findBy([], ['season' => 'ASC']),
    );

    expect($values)->toBe(['arena~autumn=2F2026' => 2, 'shed~winter' => 1])
        ->and($recordSideKeys)->toBe(['arena~autumn=2F2026', 'arena~empty', 'shed~winter'])
        ->and(array_diff(array_keys($values), $recordSideKeys))->toBe([]);
});

it('walks a dotted path off a derived identity', function (): void {
    $values = AggregateQuery::fetch(
        aggregateResidencies(),
        Residency::class,
        aggregateDeclaration('MAX', 'venue.gigs', 'attendance'),
        ['arena~autumn=2F2026', 'shed~winter', 'arena~empty'],
    );

    expect($values)->toBe(['arena~autumn=2F2026' => 900, 'arena~empty' => 900, 'shed~winter' => 50]);
});

it('keys a single derived identity by the converted identifier, not the raw column', function (): void {
    $entityManager = aggregateLockers();

    $values = AggregateQuery::fetch(
        $entityManager,
        LockerSeal::class,
        aggregateDeclaration('COUNT', 'locker.items'),
        ['AB-1', 'CD-2'],
    );

    $recordSideKeys = array_map(
        static fn (LockerSeal $seal): string => RecordKey::of($entityManager, $seal, LockerSeal::class),
        $entityManager->getRepository(LockerSeal::class)->findBy([], ['position' => 'ASC']),
    );

    expect($values)->toBe(['AB-1' => 2, 'CD-2' => 1])
        ->and($recordSideKeys)->toBe(['AB-1', 'CD-2'])
        ->and(array_keys($values))->toBe($recordSideKeys);
});

it('keeps the key narrowing over a composite root when a scope replaces the where clause', function (): void {
    $scope = static fn (QueryBuilder $query, string $alias): QueryBuilder => $query->where("{$alias}.visits > 3");

    $values = AggregateQuery::fetch(
        aggregateShortcuts(),
        Shortcut::class,
        aggregateDeclaration('COUNT', 'hits', scope: $scope),
        ['www=2Eexample=2Ecom~=2Fa=20b'],
    );

    expect($values)->toBe(['www=2Eexample=2Ecom~=2Fa=20b' => 1]);
});

it('reads a single-identifier batch in one statement, narrowed as it always was', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = aggregateFixtureEntityManager($recorder);

    $recorder->reset();

    $values = AggregateQuery::fetch($entityManager, Artist::class, aggregateDeclaration('COUNT', 'songs'), ['1', '2', '3']);

    expect($values)->toBe([1 => 2, 3 => 1])
        ->and($recorder->statements())->toBe([
            'SELECT a0_.id AS id_0, COUNT(s1_.id) AS sclr_1 FROM Artist a0_ INNER JOIN Song s1_ ON a0_.id = s1_.artist_id WHERE a0_.id IN (?, ?, ?) GROUP BY a0_.id',
        ]);
});

it('reads a composite batch wider than one chunk in one statement per chunk', function (): void {
    [$entityManager, $recorder, $keys] = aggregateChunkedShortcuts(501);

    $values = AggregateQuery::fetch($entityManager, Shortcut::class, aggregateDeclaration('COUNT', 'hits'), $keys);

    $placeholders = array_map(static fn (string $sql): int => mb_substr_count($sql, '?'), $recorder->statements());

    expect($values)->toBe(['bulk=2Etest~=2Fp=2F1' => 2, 'bulk=2Etest~=2Fp=2F501' => 2])
        ->and($recorder->statements())->toHaveCount(2)
        ->and($placeholders)->toBe([1000, 2]);
});

it('carries a scope parameter into every chunk of a composite batch', function (): void {
    [$entityManager, $recorder, $keys] = aggregateChunkedShortcuts(501);
    $scope = static fn (QueryBuilder $query, string $alias): QueryBuilder => $query
        ->andWhere("{$alias}.visits > :sylament_test_visits")
        ->setParameter('sylament_test_visits', 5);

    $values = AggregateQuery::fetch($entityManager, Shortcut::class, aggregateDeclaration('COUNT', 'hits', scope: $scope), $keys);

    expect($values)->toBe(['bulk=2Etest~=2Fp=2F1' => 1, 'bulk=2Etest~=2Fp=2F501' => 1])
        ->and($recorder->statements())->toHaveCount(2);
});

it('correlates a hidden sort select on every identifier field', function (): void {
    $entityManager = aggregateShortcuts();
    $query = $entityManager->createQueryBuilder()->select('shortcut')->from(Shortcut::class, 'shortcut');

    $alias = AggregateQuery::addHiddenSortSelect($query, Shortcut::class, aggregateDeclaration('COUNT', 'hits'));

    expect($alias)->toBe('sy_agg1')
        ->and($query->getDQL())->toContain('sy_ag1.host = shortcut.host AND sy_ag1.path = shortcut.path')
        ->and($query->getDQL())->toContain('AS HIDDEN sy_agg1');
});

it('correlates a hidden sort select on a derived identity', function (): void {
    $entityManager = aggregateResidencies();
    $query = $entityManager->createQueryBuilder()->select('residency')->from(Residency::class, 'residency');

    $alias = AggregateQuery::addHiddenSortSelect($query, Residency::class, aggregateDeclaration('COUNT', 'shows'));

    $ordered = array_map(
        static fn (Residency $residency): string => $residency->getSeason(),
        $query->addOrderBy($alias, 'DESC')->addOrderBy('residency.season', 'ASC')->getQuery()->getResult(),
    );

    expect($query->getDQL())->toContain('sy_ag1.venue = residency.venue AND sy_ag1.season = residency.season')
        ->and($ordered)->toBe(['autumn/2026', 'winter', 'empty']);
});
