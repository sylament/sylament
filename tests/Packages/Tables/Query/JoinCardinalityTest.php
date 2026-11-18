<?php

declare(strict_types=1);

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Sylament\Tables\Query\JoinCardinality;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\Product;

it('counts a join as fetching a collection only for a collection-valued association, or one the mapping cannot classify', function (Closure $build, bool $joinsCollection): void {
    expect(JoinCardinality::joinsCollection($build($this->entityManager()->createQueryBuilder())))->toBe($joinsCollection);
})->with([
    'no join' => [static fn (QueryBuilder $query): QueryBuilder => $query->select('jar')->from(LarderJar::class, 'jar'), false],
    'a to-one' => [static fn (QueryBuilder $query): QueryBuilder => $query->select('jar')->from(LarderJar::class, 'jar')->leftJoin('jar.larder', 'larder'), false],
    'a to-one off a to-one' => [static fn (QueryBuilder $query): QueryBuilder => $query->select('jar')->from(LarderJar::class, 'jar')->leftJoin('jar.larder', 'larder')->leftJoin('larder.supplier', 'supplier'), false],
    'a to-many' => [static fn (QueryBuilder $query): QueryBuilder => $query->select('larder')->from(Larder::class, 'larder')->leftJoin('larder.jars', 'jar'), true],
    'a to-many off a to-one' => [static fn (QueryBuilder $query): QueryBuilder => $query->select('jar')->from(LarderJar::class, 'jar')->leftJoin('jar.larder', 'larder')->leftJoin('larder.jars', 'sibling'), true],
    'a field that is no association' => [static fn (QueryBuilder $query): QueryBuilder => $query->select('jar')->from(LarderJar::class, 'jar')->leftJoin('jar.label', 'label'), true],
    'an alias the builder never declared' => [static fn (QueryBuilder $query): QueryBuilder => $query->select('jar')->from(LarderJar::class, 'jar')->leftJoin('pantry.jars', 'sibling'), true],
    'an arbitrary class' => [static fn (QueryBuilder $query): QueryBuilder => $query->select('jar')->from(LarderJar::class, 'jar')->leftJoin(Product::class, 'product', Join::WITH, 'product.id = jar.supplier'), true],
]);
