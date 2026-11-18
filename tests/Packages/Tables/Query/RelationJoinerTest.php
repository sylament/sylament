<?php

declare(strict_types=1);

use Sylament\Tables\Query\Hop;
use Sylament\Tables\Query\RelationJoiner;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\Seedling;

describe('ensureJoined()', function (): void {
    it('left-joins each hop under a minted alias, nested under its parent, once however often it is asked', function (): void {
        $query = $this->entityManager()->createQueryBuilder()->select('seedling')->from(Seedling::class, 'seedling');
        $hops = [new Hop('garden', Garden::class, isToMany: false), new Hop('supplier', Product::class, isToMany: false)];

        expect(RelationJoiner::ensureJoined($query, 'seedling', $hops))->toBe('sy_garden_supplier')
            ->and(RelationJoiner::ensureJoined($query, 'seedling', $hops))->toBe('sy_garden_supplier')
            ->and(RelationJoiner::ensureJoined($query, 'seedling', []))->toBe('seedling')
            ->and($query->getDQL())->toBe(sprintf('SELECT seedling FROM %s seedling LEFT JOIN seedling.garden sy_garden LEFT JOIN sy_garden.supplier sy_garden_supplier', Seedling::class));
    });
});

describe('alias minting', function (): void {
    it('suffixes past an alias the builder declares and one only a subselect in its DQL declares', function (): void {
        $oak = new Seedling('Oak');
        $oak->garden = new Garden('Kew');
        $this->seed($oak->garden, $oak);

        $declared = $this->entityManager()->createQueryBuilder()
            ->select('seedling')
            ->from(Seedling::class, 'seedling')
            ->leftJoin(Product::class, 'sy_garden', 'WITH', '1 = 1');

        $subselected = $this->entityManager()->createQueryBuilder()
            ->select('seedling')
            ->from(Seedling::class, 'seedling')
            ->where(sprintf('seedling.id IN (SELECT sy_garden.id FROM %s sy_garden)', Seedling::class));

        $alias = RelationJoiner::ensureJoined($subselected, 'seedling', [new Hop('garden', Garden::class, isToMany: false)]);
        $subselected->orderBy("{$alias}.name");

        expect(RelationJoiner::ensureJoined($declared, 'seedling', [new Hop('garden', Garden::class, isToMany: false)]))->toBe('sy_garden_2')
            ->and($alias)->toBe('sy_garden_2')
            ->and(array_map(static fn (Seedling $seedling): string => $seedling->name, $subselected->getQuery()->getResult()))->toBe(['Oak']);
    });

    it('keeps a name the DQL carries only as a parameter, a path field or part of a longer identifier free', function (): void {
        $query = $this->entityManager()->createQueryBuilder()
            ->select('seedling')
            ->from(Seedling::class, 'seedling')
            ->where('seedling.name <> :sy_garden')
            ->andWhere('seedling.sy_garden IS NULL')
            ->andWhere('sy_garden_kept.id IS NULL');

        expect(RelationJoiner::ensureJoined($query, 'seedling', [new Hop('garden', Garden::class, isToMany: false)]))->toBe('sy_garden');
    });
});
