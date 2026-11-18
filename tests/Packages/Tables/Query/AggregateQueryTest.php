<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tables\Query\AggregateDeclaration;
use Sylament\Tables\Query\AggregateQuery;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\Atelier;
use Sylament\Tests\Fixtures\Entity\AtelierEasel;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Greenhouse;
use Sylament\Tests\Fixtures\Entity\GreenhouseVent;
use Sylament\Tests\Fixtures\Entity\HexCodedSpool;
use Sylament\Tests\Fixtures\Entity\HexCodedSwatch;
use Sylament\Tests\Fixtures\Entity\HexCodedTile;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Entity\Terrace;
use Sylament\Tests\Infrastructure\Doctrine\DriverValues;
use Symfony\Component\Uid\Uuid;

describe('fetch()', function (): void {
    it('reads one figure per key over inner joins, leaving out a key with no related rows, and hands the scope the alias it aggregates', function (): void {
        $north = new Atelier('North');
        $south = new Atelier('South');
        $east = new Atelier('East');
        $this->seed($north, $south, $east, new AtelierEasel($north, 1), new AtelierEasel($north, 2), new AtelierEasel($north, 3), new AtelierEasel($south, 5));

        $sum = static fn (?Closure $scope): array => DriverValues::numeric(AggregateQuery::fetch(
            test()->entityManager(),
            Atelier::class,
            new AggregateDeclaration(function: 'SUM', relationshipPath: 'easels', column: 'position', scope: $scope, castToBool: false),
            [(string) $north->id, (string) $south->id, (string) $east->id],
        ));

        expect($sum(null))->toEqual([$north->id => 6, $south->id => 5])
            ->and($sum(static fn (QueryBuilder $query, string $alias): QueryBuilder => $query->andWhere("{$alias}.position > 1")))->toEqual([$north->id => 5, $south->id => 5]);
    });

    it('keeps its key narrowing under a scope that replaces the where clause', function (): void {
        $north = new Atelier('North');
        $south = new Atelier('South');
        $this->seed($north, $south, new AtelierEasel($north, 1), new AtelierEasel($south, 2));

        $counts = AggregateQuery::fetch(
            $this->entityManager(),
            Atelier::class,
            new AggregateDeclaration(
                function: 'COUNT',
                relationshipPath: 'easels',
                column: null,
                scope: static fn (QueryBuilder $query, string $alias): QueryBuilder => $query->where("{$alias}.position > :floor")->setParameter('floor', 0),
                castToBool: false,
            ),
            [(string) $north->id],
        );

        expect(DriverValues::numeric($counts))->toBe([$north->id => 1]);
    });

    it('refuses a row a scope read through a selection of its own, naming the key column it lacks', function (): void {
        $north = new Atelier('North');
        $this->seed($north, new AtelierEasel($north, 1));

        expect(fn (): array => AggregateQuery::fetch(
            $this->entityManager(),
            Atelier::class,
            new AggregateDeclaration(function: 'COUNT', relationshipPath: 'easels', column: null, scope: static fn (QueryBuilder $query): QueryBuilder => $query->select('COUNT(sy_t0) AS sy_value'), castToBool: false),
            [(string) $north->id],
        ))->toThrow(LogicException::class, 'A result row of [' . Atelier::class . '] lacks the key column [sy_key_0], so it was not read through the selection that keys it.');
    });

    it('reads up to two thousand single keys in one statement and more in chunks, each key keeping its own figure', function (): void {
        $north = new Atelier('North');
        $south = new Atelier('South');
        $this->seed($north, $south, new AtelierEasel($north, 1), new AtelierEasel($north, 2), new AtelierEasel($south, 3));

        $count = function (array $keys): array {
            $this->seed();

            $counts = AggregateQuery::fetch(
                $this->entityManager(),
                Atelier::class,
                new AggregateDeclaration(function: 'COUNT', relationshipPath: 'easels', column: null, scope: null, castToBool: false),
                $keys,
            );

            return [DriverValues::numeric($counts), count($this->statementsRun())];
        };

        $absent = array_map(static fn (int $offset): string => (string) (1_000_000 + $offset), range(1, 2000));

        expect($count([(string) $north->id, ...array_slice($absent, 2), (string) $south->id]))->toEqual([[$north->id => 2, $south->id => 1], 1])
            ->and($count([(string) $north->id, ...$absent, (string) $south->id]))->toEqual([[$north->id => 2, $south->id => 1], 2]);
    });

    it('keys each figure by the key the record mints, whatever its identifier', function (Closure $records, string $relationship, int $otherCount = 2): void {
        [$owner, $other, $seeded] = $records();
        $this->seed(...$seeded);

        $counts = AggregateQuery::fetch(
            $this->entityManager(),
            $owner::class,
            new AggregateDeclaration(function: 'COUNT', relationshipPath: $relationship, column: null, scope: null, castToBool: false),
            [RecordKey::of($this->entityManager(), $owner), RecordKey::of($this->entityManager(), $other)],
        );

        $expected = [RecordKey::of($this->entityManager(), $owner) => 1, RecordKey::of($this->entityManager(), $other) => $otherCount];
        $counts = DriverValues::numeric($counts);
        ksort($counts);
        ksort($expected);

        expect($counts)->toBe($expected);
    })->with([
        'two scalars' => [function (): array {
            [$owner, $other] = [new Terrace(1, 1), new Terrace(1, 2)];
            [$leek, $kale, $chard] = [new Seedling('Leek'), new Seedling('Kale'), new Seedling('Chard')];
            [$leek->terrace, $kale->terrace, $chard->terrace] = [$owner, $other, $other];

            return [$owner, $other, [$owner, $other, $leek, $kale, $chard]];
        }, 'seedlings'],
        'an association and a scalar' => [function (): array {
            $garden = new Garden('Ash');
            [$owner, $other] = [new Allotment($garden, 1), new Allotment($garden, 2)];
            [$leek, $kale, $chard] = [new Seedling('Leek'), new Seedling('Kale'), new Seedling('Chard')];
            [$leek->allotment, $kale->allotment, $chard->allotment] = [$owner, $other, $other];

            return [$owner, $other, [$garden, $owner, $other, $leek, $kale, $chard]];
        }, 'seedlings'],
        'an object identifier' => [function (): array {
            [$owner, $other] = [new Greenhouse(Uuid::v7()), new Greenhouse(Uuid::v7())];
            [$leek, $kale, $chard] = [new Seedling('Leek'), new Seedling('Kale'), new Seedling('Chard')];
            [$leek->greenhouse, $kale->greenhouse, $chard->greenhouse] = [$owner, $other, $other];

            return [$owner, $other, [$owner, $other, $leek, $kale, $chard]];
        }, 'seedlings'],
        'one association alone' => [function (): array {
            [$greenhouse, $elsewhere] = [new Greenhouse(Uuid::v7()), new Greenhouse(Uuid::v7())];
            [$owner, $other] = [new GreenhouseVent($greenhouse), new GreenhouseVent($elsewhere)];
            [$leek, $kale, $chard] = [new Seedling('Leek'), new Seedling('Kale'), new Seedling('Chard')];
            [$leek->vent, $kale->vent, $chard->vent] = [$owner, $other, $other];

            return [$owner, $other, [$greenhouse, $elsewhere, $owner, $other, $leek, $kale, $chard]];
        }, 'seedlings'],
        'a scalar whose type reads its own output as another value' => [function (): array {
            [$owner, $other, $first, $second, $third] = [new HexCodedSpool('ff'), new HexCodedSpool('a0'), new HexCodedSpool('1b'), new HexCodedSpool('2c'), new HexCodedSpool('3d')];
            [$first->core, $second->core, $third->core] = [$owner, $other, $other];

            return [$owner, $other, [$owner, $other, $first, $second, $third]];
        }, 'windings'],
        'an association onto such a target' => [function (): array {
            $swatch = new HexCodedSwatch('ff');
            [$owner, $other] = [new HexCodedTile($swatch, 'north'), new HexCodedTile($swatch, 'south')];

            return [$owner, $other, [$swatch, $owner, $other]];
        }, 'swatch', 1],
    ]);
});

describe('addHiddenSortSelect()', function (): void {
    it("orders by a hidden correlated subselect, the scope's where() keeping the correlation and its parameters carried over", function (): void {
        $east = new Atelier('East');
        $south = new Atelier('South');
        $north = new Atelier('North');
        $this->seed($east, $south, $north, new AtelierEasel($north, 1), new AtelierEasel($north, 2), new AtelierEasel($north, 3), new AtelierEasel($south, 5), new AtelierEasel($south, 0));

        $query = $this->entityManager()->createQueryBuilder()->select('atelier')->from(Atelier::class, 'atelier');
        $alias = AggregateQuery::addHiddenSortSelect($query, Atelier::class, new AggregateDeclaration(
            function: 'COUNT',
            relationshipPath: 'easels',
            column: null,
            scope: static fn (QueryBuilder $subQuery, string $easel): QueryBuilder => $subQuery->where("{$easel}.position > :floor")->setParameter('floor', 0),
            castToBool: false,
        ));
        $query->orderBy($alias, SortDirection::Descending)->addOrderBy('atelier.id', SortDirection::Ascending);

        $ateliers = $query->getQuery()->getResult();

        expect($alias)->toBe('sy_agg1')
            ->and($ateliers)->toContainOnlyInstancesOf(Atelier::class)
            ->and(array_map(static fn (Atelier $atelier): string => $atelier->name, $ateliers))->toBe(['North', 'South', 'East']);
    });
});
