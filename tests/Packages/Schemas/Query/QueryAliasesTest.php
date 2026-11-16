<?php

declare(strict_types=1);

use Sylament\Schemas\Query\QueryAliases;
use Sylament\Tests\Fixtures\Entity\Convoy;
use Sylament\Tests\Fixtures\Entity\ConvoyWagon;
use Sylament\Tests\Fixtures\Entity\Tally;

describe('classOf()', function (): void {
    it('names the entity an alias stands for, and none for an alias it cannot resolve', function (string $alias, ?string $expected): void {
        $query = $this->entityManager()->createQueryBuilder()
            ->select('wagon')
            ->from(ConvoyWagon::class, 'wagon')
            ->leftJoin('wagon.convoy', 'convoy')
            ->leftJoin('convoy.wagons', 'sibling')
            ->leftJoin(Tally::class, 'tally', 'WITH', '1 = 1')
            ->leftJoin('wagon.cargo', 'cargo')
            ->leftJoin('stranger.wagons', 'orphan');

        expect(QueryAliases::classOf($query, $alias))->toBe($expected);
    })->with([
        'a root' => ['wagon', ConvoyWagon::class],
        'a join across an association' => ['convoy', Convoy::class],
        'a join off a join' => ['sibling', ConvoyWagon::class],
        'a join naming an entity' => ['tally', Tally::class],
        'a join across a field' => ['cargo', null],
        'a join off an undeclared alias' => ['orphan', null],
        'an undeclared alias' => ['nobody', null],
    ]);
});

describe('isFetchJoinedCollection()', function (): void {
    it('is true only for a join across a collection the builder also selects', function (array $select, string $alias, bool $expected): void {
        $query = $this->entityManager()->createQueryBuilder()
            ->select(...$select)
            ->from(Convoy::class, 'convoy')
            ->leftJoin('convoy.wagons', 'wagon')
            ->leftJoin('wagon.convoy', 'owner');

        expect(QueryAliases::isFetchJoinedCollection($query, $alias))->toBe($expected);
    })->with([
        'a collection joined and selected' => [['convoy', 'wagon'], 'wagon', true],
        'a collection selected in one list' => [['convoy, wagon'], 'wagon', true],
        'a collection partially selected' => [['convoy', 'partial wagon.{id}'], 'wagon', true],
        'a collection joined and not selected' => [['convoy'], 'wagon', false],
        'a to-one join selected' => [['convoy', 'wagon', 'owner'], 'owner', false],
        'an undeclared alias' => [['convoy'], 'nobody', false],
    ]);
});
