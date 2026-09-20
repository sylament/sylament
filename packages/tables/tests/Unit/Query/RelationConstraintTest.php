<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tables\Query\Hop;
use Sylament\Tables\Query\RelationConstraint;
use Sylament\Tables\Query\ResolvedRelationPath;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Address;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Genre;
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

/**
 * One artist carrying two songs and one carrying none, so a predicate that
 * multiplied rows and one that matched every root both fail.
 */
function seededLibrary(): EntityManager
{
    $entityManager = EntityManagerFactory::create();

    $withSongs = new Artist(1, 'Radiohead', address: new Address('Main St'));
    $withoutSongs = new Artist(2, 'Silence', address: new Address('Back Rd'));

    EntityManagerFactory::seed(
        $entityManager,
        $withSongs,
        $withoutSongs,
        new Song(1, 'Alpha', 10, $withSongs),
        new Song(2, 'Beta', 20, $withSongs),
    );

    return $entityManager;
}

function compositeShortcut(string $host, string $path, string $label): Shortcut
{
    $shortcut = new Shortcut();
    $shortcut->setHost($host);
    $shortcut->setPath($path);
    $shortcut->setLabel($label);

    return $shortcut;
}

function compositeResidency(Venue $venue, string $season, string $label): Residency
{
    $residency = new Residency();
    $residency->setVenue($venue);
    $residency->setSeason($season);
    $residency->setLabel($label);

    return $residency;
}

function compositeLease(Locker $locker, Artist $artist, string $label): LockerLease
{
    $lease = new LockerLease();
    $lease->setLocker($locker);
    $lease->setArtist($artist);
    $lease->setLabel($label);

    return $lease;
}

/**
 * One record of each composite flavour carrying a collection of its own and, in
 * every flavour, a sibling sharing its first identifier field and carrying
 * none — so a correlation reading one field of a composite identifier matches a
 * root it must not. The keys carry the characters a key has to escape.
 */
function seededCompositeRelations(): EntityManager
{
    $entityManager = EntityManagerFactory::create();

    $shortcut = compositeShortcut('sylament.test', '/docs/a b', 'Docs');
    $northHall = new Venue(new Handle('north.hall'), 'North Hall');
    $southHall = new Venue(new Handle('south.hall'), 'South Hall');
    $residency = compositeResidency($northHall, 'summer-2026', 'Summer');
    $locker = new Locker('AB-1', 'Front row');
    $emptyLocker = new Locker('ZZ-9', 'Back row');
    $alpha = new Artist(1, 'Alpha', address: new Address('Main St'));
    $bravo = new Artist(2, 'Bravo', address: new Address('Back Rd'));
    $lease = compositeLease($locker, $alpha, 'Alpha at AB-1');

    EntityManagerFactory::seed(
        $entityManager,
        $shortcut,
        compositeShortcut('sylament.test', '/blog', 'Blog'),
        compositeShortcut('plain', 'about', 'About'),
        $northHall,
        $southHall,
        $residency,
        compositeResidency($northHall, 'winter-2027', 'Winter at north'),
        compositeResidency($southHall, 'winter-2026', 'Winter'),
        $locker,
        $emptyLocker,
        $alpha,
        $bravo,
        $lease,
        compositeLease($locker, $bravo, 'Bravo at AB-1'),
        new ShortcutHit(1, 'curl/8', 5, $shortcut),
        new ResidencyShow(1, 'Opening night', 200, $residency),
        new Gig(1, 'Winter gala', 300, $northHall),
        new LockerItem(1, 'Helmet', 2, $locker),
        new LockerSeal($locker, 'Sealed', 1),
        new LockerSeal($emptyLocker, 'Unsealed', 2),
        new LockerLeaseNote(1, 'Key handover', 1, $lease),
    );

    return $entityManager;
}

it('builds a re-rooted EXISTS with the like inside', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('s')->from(Song::class, 's');
    $path = new ResolvedRelationPath([new Hop('genres', Genre::class, true)], 'name');

    $dql = RelationConstraint::existsLike($query, $path, 'rock', forceCaseInsensitive: false);

    expect($dql)->toBe('EXISTS (SELECT sy_x0.id FROM ' . Song::class . ' sy_x0 JOIN sy_x0.genres sy_x0_0 WHERE sy_x0.id = s.id AND sy_x0_0.name LIKE :sylament_search_0)')
        ->and($query->getParameters())->toHaveCount(1)
        ->and($query->getParameter('sylament_search_0')?->getValue())->toBe('%rock%');
});

it('seeds subselect aliases from the parameter count so two calls never collide', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('s')->from(Song::class, 's');
    $path = new ResolvedRelationPath([new Hop('genres', Genre::class, true)], 'name');

    $first = RelationConstraint::existsLike($query, $path, 'rock', forceCaseInsensitive: false);
    $second = RelationConstraint::existsLike($query, $path, 'jazz', forceCaseInsensitive: false);

    expect($first)->toContain('sy_x0 JOIN sy_x0.genres sy_x0_0', 'sy_x0_0.name LIKE :sylament_search_0')
        ->and($second)->toContain('sy_x1 JOIN sy_x1.genres sy_x1_0', 'sy_x1_0.name LIKE :sylament_search_1')
        ->and($query->getParameters())->toHaveCount(2)
        ->and($query->getParameter('sylament_search_1')?->getValue())->toBe('%jazz%');
});

it('chains a join alias per hop', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('a')->from(Artist::class, 'a');
    $path = new ResolvedRelationPath([new Hop('songs', Song::class, true), new Hop('genres', Genre::class, true)], 'name');

    $dql = RelationConstraint::existsLike($query, $path, 'rock', forceCaseInsensitive: true);

    expect($dql)->toBe('EXISTS (SELECT sy_x0.id FROM ' . Artist::class . ' sy_x0 JOIN sy_x0.songs sy_x0_0 JOIN sy_x0_0.genres sy_x0_1 WHERE sy_x0.id = a.id AND LOWER(sy_x0_1.name) LIKE :sylament_search_0)');
});

it('keeps a dotted embeddable field path intact', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('s')->from(Song::class, 's');
    $path = new ResolvedRelationPath([new Hop('artist', Artist::class, false)], 'address.street');

    $dql = RelationConstraint::existsLike($query, $path, 'Main', forceCaseInsensitive: false);

    expect($dql)->toBe('EXISTS (SELECT sy_x0.id FROM ' . Song::class . ' sy_x0 JOIN sy_x0.artist sy_x0_0 WHERE sy_x0.id = s.id AND sy_x0_0.address.street LIKE :sylament_search_0)');
});

it('projects the first identifier field of a scalar pair and correlates both', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('shortcut')->from(Shortcut::class, 'shortcut');
    $path = new ResolvedRelationPath([new Hop('hits', ShortcutHit::class, true)], 'agent');

    $dql = RelationConstraint::existsLike($query, $path, 'curl', forceCaseInsensitive: false);

    expect($dql)->toBe('EXISTS (SELECT sy_x0.host FROM ' . Shortcut::class . ' sy_x0 JOIN sy_x0.hits sy_x0_0 WHERE sy_x0.host = shortcut.host AND sy_x0.path = shortcut.path AND sy_x0_0.agent LIKE :sylament_search_0)');
});

it('wraps an identifier association in IDENTITY and correlates the whole derived identity', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('residency')->from(Residency::class, 'residency');
    $path = new ResolvedRelationPath([new Hop('shows', ResidencyShow::class, true)], 'title');

    $dql = RelationConstraint::existsLike($query, $path, 'opening', forceCaseInsensitive: false);

    expect($dql)->toBe('EXISTS (SELECT IDENTITY(sy_x0.venue) FROM ' . Residency::class . ' sy_x0 JOIN sy_x0.shows sy_x0_0 WHERE sy_x0.venue = residency.venue AND sy_x0.season = residency.season AND sy_x0_0.title LIKE :sylament_search_0)');
});

it('projects a single derived identity through IDENTITY, which a bare association path cannot be', function (): void {
    $entityManager = seededCompositeRelations();
    $query = $entityManager->createQueryBuilder()->select('seal')->from(LockerSeal::class, 'seal');
    $path = new ResolvedRelationPath([new Hop('locker', Locker::class, false)], 'label');

    $dql = RelationConstraint::existsLike($query, $path, 'front', forceCaseInsensitive: false);

    $query->andWhere($dql);

    expect($dql)->toBe('EXISTS (SELECT IDENTITY(sy_x0.locker) FROM ' . LockerSeal::class . ' sy_x0 JOIN sy_x0.locker sy_x0_0 WHERE sy_x0.locker = seal.locker AND sy_x0_0.label LIKE :sylament_search_0)')
        ->and($query->getQuery()->getSQL())->toStartWith('SELECT');
});

it('matches a scalar-pair root by a value on its own collection', function (): void {
    $entityManager = seededCompositeRelations();
    $query = $entityManager->createQueryBuilder()->select('shortcut')->from(Shortcut::class, 'shortcut');
    $path = new ResolvedRelationPath([new Hop('hits', ShortcutHit::class, true)], 'agent');

    $query->andWhere(RelationConstraint::existsLike($query, $path, 'curl', forceCaseInsensitive: false));

    $matches = $query->getQuery()->getResult();

    expect($matches)->toHaveCount(1)
        ->and(RecordKey::of($entityManager, $matches[0]))->toBe('sylament=2Etest~=2Fdocs=2Fa=20b');
});

it('matches a derived-identity root by a value on its own collection', function (): void {
    $entityManager = seededCompositeRelations();
    $query = $entityManager->createQueryBuilder()->select('residency')->from(Residency::class, 'residency');
    $path = new ResolvedRelationPath([new Hop('shows', ResidencyShow::class, true)], 'title');

    $query->andWhere(RelationConstraint::existsLike($query, $path, 'opening', forceCaseInsensitive: false));

    $matches = $query->getQuery()->getResult();

    expect($matches)->toHaveCount(1)
        ->and(RecordKey::of($entityManager, $matches[0]))->toBe('north=2Ehall~summer-2026');
});

it('crosses a to-one hop that is itself an identifier association', function (): void {
    $entityManager = seededCompositeRelations();
    $query = $entityManager->createQueryBuilder()->select('residency')->from(Residency::class, 'residency')->orderBy('residency.season');
    $path = new ResolvedRelationPath([new Hop('venue', Venue::class, false)], 'name');

    $dql = RelationConstraint::existsLike($query, $path, 'north', forceCaseInsensitive: false);

    $query->andWhere($dql);

    $matches = $query->getQuery()->getResult();

    expect($dql)->toBe('EXISTS (SELECT IDENTITY(sy_x0.venue) FROM ' . Residency::class . ' sy_x0 JOIN sy_x0.venue sy_x0_0 WHERE sy_x0.venue = residency.venue AND sy_x0.season = residency.season AND sy_x0_0.name LIKE :sylament_search_0)')
        ->and(array_map(static fn (Residency $record): string => RecordKey::of($entityManager, $record), $matches))
        ->toBe(['north=2Ehall~summer-2026', 'north=2Ehall~winter-2027']);
});

it('crosses an identifier hop and then a collection of its target', function (): void {
    $entityManager = seededCompositeRelations();
    $query = $entityManager->createQueryBuilder()->select('residency')->from(Residency::class, 'residency')->orderBy('residency.season');
    $path = new ResolvedRelationPath([new Hop('venue', Venue::class, false), new Hop('gigs', Gig::class, true)], 'title');

    $dql = RelationConstraint::existsLike($query, $path, 'gala', forceCaseInsensitive: false);

    $query->andWhere($dql);

    $matches = $query->getQuery()->getResult();

    expect($dql)->toContain('JOIN sy_x0.venue sy_x0_0 JOIN sy_x0_0.gigs sy_x0_1')
        ->and(array_map(static fn (Residency $record): string => RecordKey::of($entityManager, $record), $matches))
        ->toBe(['north=2Ehall~summer-2026', 'north=2Ehall~winter-2027']);
});

it('matches a single-derived-identity root across its identifier association', function (): void {
    $entityManager = seededCompositeRelations();
    $query = $entityManager->createQueryBuilder()->select('seal')->from(LockerSeal::class, 'seal');
    $path = new ResolvedRelationPath([new Hop('locker', Locker::class, false)], 'label');

    $query->andWhere(RelationConstraint::existsLike($query, $path, 'front', forceCaseInsensitive: false));

    $matches = $query->getQuery()->getResult();

    expect($matches)->toHaveCount(1)
        ->and(RecordKey::of($entityManager, $matches[0]))->toBe('AB-1');
});

it('matches a single-derived-identity root by a collection two hops out', function (): void {
    $entityManager = seededCompositeRelations();
    $query = $entityManager->createQueryBuilder()->select('seal')->from(LockerSeal::class, 'seal');
    $path = new ResolvedRelationPath([new Hop('locker', Locker::class, false), new Hop('items', LockerItem::class, true)], 'name');

    $dql = RelationConstraint::existsLike($query, $path, 'helmet', forceCaseInsensitive: true);

    $query->andWhere($dql);

    $matches = $query->getQuery()->getResult();

    expect($dql)->toContain('JOIN sy_x0.locker sy_x0_0 JOIN sy_x0_0.items sy_x0_1')
        ->and($matches)->toHaveCount(1)
        ->and(RecordKey::of($entityManager, $matches[0]))->toBe('AB-1');
});

/*
 * Both identifier fields are associations here, so the projection wraps the
 * first in `IDENTITY()` and the correlation names them both — and the sibling
 * lease sharing the locker half carries no note, which is what a correlation
 * one field short would match anyway.
 */
it('matches a root keyed by two associations through a collection of its own', function (): void {
    $entityManager = seededCompositeRelations();
    $query = $entityManager->createQueryBuilder()->select('lease')->from(LockerLease::class, 'lease');
    $path = new ResolvedRelationPath([new Hop('notes', LockerLeaseNote::class, true)], 'heading');

    $dql = RelationConstraint::existsLike($query, $path, 'handover', forceCaseInsensitive: false);

    $query->andWhere($dql);

    $matches = $query->getQuery()->getResult();

    expect($dql)->toBe('EXISTS (SELECT IDENTITY(sy_x0.locker) FROM ' . LockerLease::class . ' sy_x0 JOIN sy_x0.notes sy_x0_0 WHERE sy_x0.locker = lease.locker AND sy_x0.artist = lease.artist AND sy_x0_0.heading LIKE :sylament_search_0)')
        ->and($matches)->toHaveCount(1)
        ->and(RecordKey::of($entityManager, $matches[0]))->toBe('AB-1~1');
});

it('keeps subselect aliases distinct when a by-key predicate binds between two calls', function (): void {
    $entityManager = seededCompositeRelations();
    $query = $entityManager->createQueryBuilder()->select('residency')->from(Residency::class, 'residency');
    $path = new ResolvedRelationPath([new Hop('shows', ResidencyShow::class, true)], 'title');

    $first = RelationConstraint::existsLike($query, $path, 'opening', forceCaseInsensitive: false);

    $query->andWhere(IdentifierConstraint::in($query, 'residency', Residency::class, ['north=2Ehall~summer-2026'], 'sylament_selected_record_keys'));

    $second = RelationConstraint::existsLike($query, $path, 'night', forceCaseInsensitive: false);

    $query->andWhere($first)->andWhere($second);

    expect($first)->toContain('SELECT IDENTITY(sy_x0.venue)', 'sy_x0_0.title LIKE :sylament_search_0')
        ->and($second)->toContain('SELECT IDENTITY(sy_x3.venue)', 'sy_x3_0.title LIKE :sylament_search_3')
        ->and($query->getQuery()->getResult())->toHaveCount(1);
});

it('refuses a query that declares no root alias', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('s');
    $path = new ResolvedRelationPath([new Hop('genres', Genre::class, true)], 'name');

    RelationConstraint::existsLike($query, $path, 'rock', forceCaseInsensitive: false);
})->throws(LogicException::class, 'no root alias');

it('matches roots by related values without multiplying rows', function (): void {
    $entityManager = seededLibrary();

    $query = $entityManager->createQueryBuilder()->select('a')->from(Artist::class, 'a');
    $path = new ResolvedRelationPath([new Hop('songs', Song::class, true)], 'title');

    $query->andWhere(RelationConstraint::existsLike($query, $path, 'a', forceCaseInsensitive: true));

    $matches = $query->getQuery()->getResult();

    expect($matches)->toHaveCount(1)
        ->and($matches[0])->toBeInstanceOf(Artist::class)
        ->and($matches[0]->getName())->toBe('Radiohead');
});

it('matches no root when no related value matches', function (): void {
    $entityManager = seededLibrary();

    $query = $entityManager->createQueryBuilder()->select('a')->from(Artist::class, 'a');
    $path = new ResolvedRelationPath([new Hop('songs', Song::class, true)], 'title');

    $query->andWhere(RelationConstraint::existsLike($query, $path, 'zzz', forceCaseInsensitive: true));

    expect($query->getQuery()->getResult())->toBe([]);
});

it('matches an embeddable field across a to-one hop', function (): void {
    $entityManager = seededLibrary();

    $query = $entityManager->createQueryBuilder()->select('s')->from(Song::class, 's')->orderBy('s.id');
    $path = new ResolvedRelationPath([new Hop('artist', Artist::class, false)], 'address.street');

    $query->andWhere(RelationConstraint::existsLike($query, $path, 'main', forceCaseInsensitive: true));

    $matches = $query->getQuery()->getResult();

    expect($matches)->toHaveCount(2)
        ->and(array_map(static fn (Song $song): string => $song->getTitle(), $matches))->toBe(['Alpha', 'Beta']);
});
