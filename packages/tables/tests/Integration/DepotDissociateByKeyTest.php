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
use Sylament\Tables\Tests\Fixtures\Doctrine\RecordingMiddleware;
use Sylament\Tables\Tests\Fixtures\Twig\Components\DepotDissociateByKeyTable;
use Sylament\Tables\Tests\Fixtures\Twig\Components\DepotQueryBesideRelationshipTable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The by-key dissociate writes one SQL `UPDATE` of the table that holds the
 * mapped-by's foreign key, so the three shapes where that table or its columns
 * are not the target class's own bare names are pinned here, through the real
 * action: a to-one a joined leaf inherits from its root, reserved words the
 * mapping quotes, and a single-table leaf — whose statement carries no
 * discriminator, because the keys it narrows by were plucked through the
 * table's own query, which does. The last two cases are that pluck, from both
 * sides: a selected key the relationship query excludes is never written, and
 * the same key IS written when the table declares a `query()` of its own, which
 * wins over the relationship and plucks the whole root — the one shape where the
 * write escapes both narrowings the relationship query carried, so a sibling of
 * another class in that table and a row of another owner are written alike,
 * recorded rather than refused.
 *
 * Every expected statement is the driver's own text, as a string literal.
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

it('writes the joined root table, which holds the foreign key a leaf inherits', function (): void {
    [$client, $entityManager, $recorder] = depotByKeySeed();
    $select = 'SELECT depot_id FROM Appliance WHERE appliance_id = ?';

    expect(depotByKeyUpdates($client, $recorder, 'toasters', ['1']))->toBe(['UPDATE Appliance SET depot_id = NULL WHERE appliance_id IN (?)'])
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

it('writes a single-table leaf with no discriminator predicate', function (): void {
    [$client, $entityManager, $recorder] = depotByKeySeed();
    $select = 'SELECT depot_id FROM Gauge WHERE id = ?';

    expect(depotByKeyUpdates($client, $recorder, 'dials', ['1']))->toBe(['UPDATE Gauge SET depot_id = NULL WHERE id IN (?)'])
        ->and(depotByKeyStoredDepot($entityManager, $select, 1))->toBe(['depot_id' => null])
        ->and(depotByKeyStoredDepot($entityManager, $select, 2))->toBe(['depot_id' => 1])
        ->and(depotByKeyRelatedKeys($client, $entityManager, 'dials'))->toBe([2]);
});

it('dissociates nothing a selected key names outside the table query', function (string $association, string $update, string $select, int $excluded, int $depot): void {
    [$client, $entityManager, $recorder] = depotByKeySeed();

    expect(depotByKeyUpdates($client, $recorder, $association, ['1', (string) $excluded]))->toBe([$update])
        ->and(depotByKeyStoredDepot($entityManager, $select, 1))->toBe(['depot_id' => null])
        ->and(depotByKeyStoredDepot($entityManager, $select, $excluded))->toBe(['depot_id' => $depot]);
})->with([
    "the second depot's toaster" => ['toasters', 'UPDATE Appliance SET depot_id = NULL WHERE appliance_id IN (?)', 'SELECT depot_id FROM Appliance WHERE appliance_id = ?', 3, 2],
    'a plain gauge of this depot, beside its dials' => ['dials', 'UPDATE Gauge SET depot_id = NULL WHERE id IN (?)', 'SELECT depot_id FROM Gauge WHERE id = ?', 3, 1],
]);

it('writes a single-table sibling and a dial of another depot a declared query() plucks, the statement carrying neither a discriminator nor an owner to stop it', function (): void {
    [$client, $entityManager, $recorder] = depotByKeySeed();
    $select = 'SELECT depot_id FROM Gauge WHERE id = ?';

    expect(depotByKeyQuerySideUpdates($client, $recorder, ['1', '3', '4']))->toBe(['UPDATE Gauge SET depot_id = NULL WHERE id IN (?, ?, ?)'])
        ->and(depotByKeyStoredDepot($entityManager, $select, 1))->toBe(['depot_id' => null])
        ->and(depotByKeyStoredDepot($entityManager, $select, 3))->toBe(['depot_id' => null])
        ->and(depotByKeyStoredDepot($entityManager, $select, 4))->toBe(['depot_id' => null])
        ->and(depotByKeyStoredDepot($entityManager, $select, 2))->toBe(['depot_id' => 1]);
});
