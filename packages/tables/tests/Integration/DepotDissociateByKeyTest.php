<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Bazaar;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Depot;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Dial;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Gauge;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Purchase;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Toaster;
use Sylament\Tables\Tests\Fixtures\Twig\Components\DepotDissociateByKeyTable;
use Sylament\Tables\Tests\Fixtures\Twig\Components\DepotQueryBesideRelationshipTable;
use Sylament\Testing\Doctrine\RecordingMiddleware;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The by-key dissociate nulls the mapped-by's foreign key of the plucked keys,
 * and the statement it writes depends on how many columns that key spans. One
 * column is a DQL `UPDATE`, so Doctrine's walker adds what it adds to any DQL
 * update: a single-table leaf's discriminator, and for a joined leaf the
 * multi-table executor, which narrows by the leaf table. Several columns — only
 * a composite owner has them — are one DBAL `UPDATE` through the quote
 * strategy, pinned here by the reserved-word case. The pluck cases show both
 * narrowings: a selected key the relationship query excludes is never written,
 * and under a table that declares `query()` beside `relationship()` a dial of
 * another depot is written while the single-table sibling is not.
 *
 * Every expected statement is the driver's own text, as a string literal, except
 * the joined leaf's, whose temporary-table name is Doctrine's to mint.
 */

beforeEach(function (): void {
    $request = new Request();
    $request->setSession($this::getContainer()->get('session.factory')->createSession());
    $this::getContainer()->get(RequestStack::class)->push($request);
});

/**
 * Two depots and two bazaars, each collection holding two records of the first
 * owner beside one of the second — plus a plain gauge of the first depot
 * sharing the dials' table.
 *
 * @return array{KernelBrowser, EntityManagerInterface, RecordingMiddleware}
 */
function depotByKeySeed(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $recorder = test()->getContainer()->get(RecordingMiddleware::class);

    $first = new Depot(1, 'First');
    $second = new Depot(2, 'Second');
    $north = new Bazaar('north', 'one', 'North');
    $south = new Bazaar('south', 'two', 'South');

    foreach ([
        $first,
        $second,
        $north,
        $south,
        new Toaster(1, 'Two-slot', $first),
        new Toaster(2, 'Four-slot', $first),
        new Toaster(3, 'Travel', $second),
        new Dial(1, 'Pressure', $first),
        new Dial(2, 'Humidity', $first),
        new Gauge(3, 'Plain', $first),
        new Dial(4, 'Altitude', $second),
        new Purchase(1, 'Kettles', $north),
        new Purchase(2, 'Mugs', $north),
        new Purchase(3, 'Spoons', $south),
    ] as $entity) {
        $entityManager->persist($entity);
    }

    $entityManager->flush();
    $recorder->reset();

    return [$client, $entityManager, $recorder];
}

/**
 * The confirmed by-key dissociate over a selection, answering the `UPDATE`
 * statements the action body ran.
 *
 * @param list<string> $selection
 *
 * @return list<string>
 */
function depotByKeyUpdates(KernelBrowser $client, RecordingMiddleware $recorder, string $association, array $selection): array
{
    $component = test()->createLiveComponent(DepotDissociateByKeyTable::class, ['association' => $association], client: $client)
        ->set('selectedTableRecords', $selection)
        ->call('mountAction', ['name' => 'dissociate', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]]);

    $recorder->reset();
    $component->call('callMountedAction');

    return array_values(array_filter($recorder->statements(), static fn (string $sql): bool => str_starts_with($sql, 'UPDATE')));
}

/**
 * The same confirmed by-key dissociate, driven through the host that declares
 * `query()` beside `relationship()`.
 *
 * @param list<string> $selection
 *
 * @return list<string>
 */
function depotByKeyQuerySideUpdates(KernelBrowser $client, RecordingMiddleware $recorder, array $selection): array
{
    $component = test()->createLiveComponent(DepotQueryBesideRelationshipTable::class, client: $client)
        ->set('selectedTableRecords', $selection)
        ->call('mountAction', ['name' => 'dissociate', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]]);

    $recorder->reset();
    $component->call('callMountedAction');

    return array_values(array_filter($recorder->statements(), static fn (string $sql): bool => str_starts_with($sql, 'UPDATE')));
}

/**
 * The foreign-key columns one row stores, read straight off the connection.
 *
 * @return array<string, mixed>
 */
function depotByKeyStoredDepot(EntityManagerInterface $entityManager, string $select, int $id): array
{
    $row = $entityManager->getConnection()->fetchAssociative($select, [$id]);

    return $row === false ? [] : $row;
}

/**
 * @return list<int|string>
 */
function depotByKeyRelatedKeys(KernelBrowser $client, EntityManagerInterface $entityManager, string $association): array
{
    $entityManager->clear();

    return array_keys(test()->createLiveComponent(DepotDissociateByKeyTable::class, ['association' => $association], client: $client)->component()->getTable()->getRecords());
}

it("writes a joined leaf through Doctrine's multi-table update, narrowed by the leaf table", function (): void {
    [$client, $entityManager, $recorder] = depotByKeySeed();
    $select = 'SELECT depot_id FROM Appliance WHERE appliance_id = ?';

    $updates = depotByKeyUpdates($client, $recorder, 'toasters', ['1']);

    expect($updates)->toHaveCount(1)
        ->and($updates[0])->toStartWith('UPDATE Appliance SET depot_id = NULL WHERE')
        ->and($updates[0])->toContain('IN (SELECT')
        ->and(depotByKeyStoredDepot($entityManager, $select, 1))->toBe(['depot_id' => null])
        ->and(depotByKeyStoredDepot($entityManager, $select, 2))->toBe(['depot_id' => 1])
        ->and(depotByKeyRelatedKeys($client, $entityManager, 'toasters'))->toBe([2]);
});

it('names a reserved-word table, its identifier and its join columns through the quote strategy', function (): void {
    [$client, $entityManager, $recorder] = depotByKeySeed();
    $select = 'SELECT "select", "from" FROM "order" WHERE "group" = ?';

    expect(depotByKeyUpdates($client, $recorder, 'purchases', ['1']))->toBe(['UPDATE "order" SET "select" = NULL, "from" = NULL WHERE "group" IN (?)'])
        ->and(depotByKeyStoredDepot($entityManager, $select, 1))->toBe(['select' => null, 'from' => null])
        ->and(depotByKeyStoredDepot($entityManager, $select, 2))->toBe(['select' => 'north', 'from' => 'one'])
        ->and(depotByKeyRelatedKeys($client, $entityManager, 'purchases'))->toBe([2]);
});

it('writes a single-table leaf with its discriminator predicate', function (): void {
    [$client, $entityManager, $recorder] = depotByKeySeed();
    $select = 'SELECT depot_id FROM Gauge WHERE id = ?';

    expect(depotByKeyUpdates($client, $recorder, 'dials', ['1']))->toBe(["UPDATE Gauge SET depot_id = NULL WHERE (id IN (?)) AND kind IN ('dial')"])
        ->and(depotByKeyStoredDepot($entityManager, $select, 1))->toBe(['depot_id' => null])
        ->and(depotByKeyStoredDepot($entityManager, $select, 2))->toBe(['depot_id' => 1])
        ->and(depotByKeyRelatedKeys($client, $entityManager, 'dials'))->toBe([2]);
});

it('dissociates no toaster of another depot a selected key names', function (): void {
    [$client, $entityManager, $recorder] = depotByKeySeed();
    $select = 'SELECT depot_id FROM Appliance WHERE appliance_id = ?';

    expect(depotByKeyUpdates($client, $recorder, 'toasters', ['1', '3']))->toHaveCount(1)
        ->and(depotByKeyStoredDepot($entityManager, $select, 1))->toBe(['depot_id' => null])
        ->and(depotByKeyStoredDepot($entityManager, $select, 3))->toBe(['depot_id' => 2]);
});

it('dissociates no plain gauge beside the dials a selected key names', function (): void {
    [$client, $entityManager, $recorder] = depotByKeySeed();
    $select = 'SELECT depot_id FROM Gauge WHERE id = ?';

    expect(depotByKeyUpdates($client, $recorder, 'dials', ['1', '3']))->toBe(["UPDATE Gauge SET depot_id = NULL WHERE (id IN (?)) AND kind IN ('dial')"])
        ->and(depotByKeyStoredDepot($entityManager, $select, 1))->toBe(['depot_id' => null])
        ->and(depotByKeyStoredDepot($entityManager, $select, 3))->toBe(['depot_id' => 1]);
});

it('writes a dial of another depot a declared query() plucks and leaves the single-table sibling, the statement carrying the discriminator and no owner', function (): void {
    [$client, $entityManager, $recorder] = depotByKeySeed();
    $select = 'SELECT depot_id FROM Gauge WHERE id = ?';

    expect(depotByKeyQuerySideUpdates($client, $recorder, ['1', '3', '4']))->toBe(["UPDATE Gauge SET depot_id = NULL WHERE (id IN (?, ?, ?)) AND kind IN ('dial')"])
        ->and(depotByKeyStoredDepot($entityManager, $select, 1))->toBe(['depot_id' => null])
        ->and(depotByKeyStoredDepot($entityManager, $select, 3))->toBe(['depot_id' => 1])
        ->and(depotByKeyStoredDepot($entityManager, $select, 4))->toBe(['depot_id' => null])
        ->and(depotByKeyStoredDepot($entityManager, $select, 2))->toBe(['depot_id' => 1]);
});
