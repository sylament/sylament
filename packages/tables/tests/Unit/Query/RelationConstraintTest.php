<?php

declare(strict_types=1);

use Sylament\Tables\Query\Hop;
use Sylament\Tables\Query\RelationConstraint;
use Sylament\Tables\Query\ResolvedRelationPath;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\CompositePair;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Genre;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;

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

it('refuses a composite-identifier root', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('p')->from(CompositePair::class, 'p');
    $path = new ResolvedRelationPath([new Hop('genres', Genre::class, true)], 'name');

    RelationConstraint::existsLike($query, $path, 'rock', forceCaseInsensitive: false);
})->throws(LogicException::class, 'composite identifier');

it('refuses a query that declares no root alias', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('s');
    $path = new ResolvedRelationPath([new Hop('genres', Genre::class, true)], 'name');

    RelationConstraint::existsLike($query, $path, 'rock', forceCaseInsensitive: false);
})->throws(LogicException::class, 'no root alias');
