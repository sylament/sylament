<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Sylament\Tables\Query\Hop;
use Sylament\Tables\Query\RelationConstraint;
use Sylament\Tables\Query\ResolvedRelationPath;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Address;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;

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
