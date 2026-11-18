<?php

declare(strict_types=1);

use Doctrine\ORM\Query\QueryException;
use Sylament\Tables\Query\FetchJoinPageQuery;
use Sylament\Tests\Fixtures\Entity\CachedLantern;
use Sylament\Tests\Fixtures\Entity\Greenhouse;
use Sylament\Tests\Fixtures\Entity\GreenhouseVent;
use Sylament\Tests\Fixtures\Entity\HexCodedSpool;
use Sylament\Tests\Fixtures\Entity\LanternStand;
use Sylament\Tests\Fixtures\Entity\Lighthouse;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Symfony\Component\Uid\Uuid;

describe('the window', function (): void {
    beforeEach(function (): void {
        $records = [];

        foreach ([[1, 1], [1, 2], [2, 1], [2, 2]] as [$aisle, $bay]) {
            $records[] = $stand = new LanternStand($aisle, $bay);

            foreach (['amber', 'brass'] as $name) {
                $records[] = $lantern = new CachedLantern("{$aisle}.{$bay} {$name}");
                $lantern->stand = $stand;
            }
        }

        $this->seed(...$records);

        $this->query = $this->entityManager()->createQueryBuilder()
            ->select('stand', 'lantern')
            ->from(LanternStand::class, 'stand')
            ->leftJoin('stand.lanterns', 'lantern')
            ->orderBy('stand.bay', SortDirection::Descending)
            ->addOrderBy('stand.aisle', SortDirection::Ascending);
    });

    it("reads the window it is asked for in the builder's own order, every collection whole, in two statements", function (): void {
        $records = FetchJoinPageQuery::read($this->query, LanternStand::class, 1, 2);
        $names = static function (LanternStand $stand): array {
            $names = array_map(static fn (CachedLantern $lantern): string => $lantern->name, $stand->lanterns->toArray());
            sort($names);

            return $names;
        };

        expect($this->statementsRun())->toHaveCount(2)
            ->and(array_map(static fn (LanternStand $stand): array => [$stand->aisle, $stand->bay, $names($stand)], $records))
            ->toBe([[2, 2, ['2.2 amber', '2.2 brass']], [1, 1, ['1.1 amber', '1.1 brass']]]);
    });

    it('answers an empty window after its first statement', function (): void {
        expect(FetchJoinPageQuery::read($this->query, LanternStand::class, 99, 2))->toBe([])
            ->and($this->statementsRun())->toHaveCount(1);
    });

    it('answers a list over a root its query indexes by a field', function (): void {
        $records = FetchJoinPageQuery::read($this->query->indexBy('stand', 'stand.bay'), LanternStand::class, 1, 2);

        expect(array_is_list($records))->toBeTrue()
            ->and(array_map(static fn (LanternStand $stand): array => [$stand->aisle, $stand->bay], $records))->toBe([[2, 2], [1, 1]]);
    });

    it('leaves the builder it reads unwindowed, unnarrowed and in its own order', function (): void {
        $query = $this->query;
        $dql = $query->getDQL();

        FetchJoinPageQuery::read($query, LanternStand::class, 1, 2);

        expect($query->getDQL())->toBe($dql)
            ->and($query->getFirstResult())->toBe(0)
            ->and($query->getMaxResults())->toBeNull()
            ->and($query->getParameters()->toArray())->toBe([]);
    });
});

describe('the order', function (): void {
    it('orders the window of a root keyed by one association by that association, on every platform', function (): void {
        $records = [];

        foreach (['1', '2', '3'] as $digit) {
            $records[] = $vent = new GreenhouseVent($records[] = new Greenhouse(Uuid::fromString("00000000-0000-4000-8000-00000000000{$digit}")));

            foreach (['fern', 'moss'] as $name) {
                $records[] = $seedling = new Seedling("{$digit} {$name}");
                $seedling->vent = $vent;
            }
        }

        $this->seed(...$records);

        $query = $this->entityManager()->createQueryBuilder()
            ->select('vent', 'seedling')
            ->from(GreenhouseVent::class, 'vent')
            ->leftJoin('vent.seedlings', 'seedling')
            ->orderBy('vent.greenhouse', SortDirection::Descending);

        $seedlings = static function (GreenhouseVent $vent): array {
            $names = array_map(static fn (Seedling $seedling): string => $seedling->name, $vent->seedlings->toArray());
            sort($names);

            return $names;
        };

        expect(array_map(static fn (GreenhouseVent $vent): array => [$vent->greenhouse->id->toString(), $seedlings($vent)], FetchJoinPageQuery::read($query, GreenhouseVent::class, 0, 2)))
            ->toBe([
                ['00000000-0000-4000-8000-000000000003', ['3 fern', '3 moss']],
                ['00000000-0000-4000-8000-000000000002', ['2 fern', '2 moss']],
            ]);
    });

    it('leaves Doctrine to refuse an order item over an inverse side or a two-column association, before its first statement on an empty window too', function (string $class, string $alias, string $item, string $message): void {
        $query = $this->entityManager()->createQueryBuilder()->select($alias)->from($class, $alias)->orderBy($item);

        expect(fn (): array => FetchJoinPageQuery::read($query, $class, 99, 2))->toThrow(QueryException::class, $message)
            ->and($this->statementsRun())->toBe([]);
    })->with([
        'the inverse side of a one-to-one' => [Lighthouse::class, 'lighthouse', 'lighthouse.log', 'A single-valued association path expression to an inverse side is not supported in DQL queries.'],
        'a to-one onto a two-column key' => [CachedLantern::class, 'lantern', 'lantern.stand', 'A single-valued association path expression to an entity with a composite primary key is not supported.'],
    ]);
});

it('keys the window of a root whose identifier type reads its own output as another value by the key each record mints', function (): void {
    $records = [];

    foreach (['c' => [], '1a' => ['101'], 'ff' => ['102', '103']] as $code => $windings) {
        $records[] = $core = new HexCodedSpool($code);

        foreach ($windings as $winding) {
            $records[] = $spool = new HexCodedSpool($winding);
            $spool->core = $core;
        }
    }

    $this->seed(...$records);

    $query = $this->entityManager()->createQueryBuilder()
        ->select('spool', 'winding')
        ->from(HexCodedSpool::class, 'spool')
        ->leftJoin('spool.windings', 'winding')
        ->where('spool.core IS NULL')
        ->orderBy('spool.code');

    expect(array_map(static fn (HexCodedSpool $spool): array => [$spool->code, $spool->windings->count()], FetchJoinPageQuery::read($query, HexCodedSpool::class, 0, 3)))
        ->toBe([['c', 0], ['1a', 1], ['ff', 2]]);
});
