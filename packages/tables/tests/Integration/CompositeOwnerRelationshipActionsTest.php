<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Forms\Components\Select;
use Sylament\Notifications\Delivery\SessionNotifications;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Circuit;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\CircuitStop;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\ResidencyRider;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\ShortcutNote;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;
use Sylament\Tables\Tests\Fixtures\Doctrine\RecordingMiddleware;
use Sylament\Tables\Tests\Fixtures\Twig\Components\CompositeOwnerRelationshipActionsManyToManyTable;
use Sylament\Tables\Tests\Fixtures\Twig\Components\CompositeOwnerRelationshipActionsOneToManyTable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The whole relationship-action family over a relation whose **owner** is keyed
 * by several fields — a scalar pair, a derived identity, and a scalar pair
 * whose many-to-many target is composite too. Every one of these cells is the
 * owner refusal at the epic's base; what makes them pass is the reader routing
 * both relationship queries through the correlated subselect, so this file is
 * where the action family's half of that routing is pinned.
 *
 * It sits in the tables suite for the reason `AttachDetachActionsTest` does:
 * only a table host supplies a relationship and a selection, and the actions
 * test kernel registers neither the tables nor the forms bundle.
 *
 * One arm does not fall out of the reader. `DissociateBulkAction`'s
 * `fetchSelectedRecords(false)` arm writes a DQL `UPDATE … SET <mappedBy> =
 * NULL`, and DQL cannot name a to-one that spans several join columns — which
 * under a composite owner is *every* one-to-many, since the mapped-by is a
 * foreign key onto the owner's whole identifier. That arm is refused by name,
 * before the `try`, so the developer sees the refusal instead of a danger toast
 * over records that all stayed owned.
 *
 * Every expected key is a string literal: minting one through `RecordKey` would
 * make the expectation agree with whatever format the codec happens to print.
 */

beforeEach(function (): void {
    $request = new Request();
    $request->setSession($this::getContainer()->get('session.factory')->createSession());
    $this::getContainer()->get(RequestStack::class)->push($request);
});

/**
 * One record of each composite flavour, each already holding one member of each
 * of its collections so every candidate query has something to exclude, and
 * each collection carrying one record that belongs to nobody and one that
 * belongs to somebody else. The identity map is left intact, the way
 * `seededGenresClient()` seeds.
 *
 * @return array{KernelBrowser, EntityManagerInterface, RecordingMiddleware}
 */
function compositeOwnerActionsSeed(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $recorder = test()->getContainer()->get(RecordingMiddleware::class);

    $artists = [new Artist(1, 'Ada'), new Artist(2, 'Bob'), new Artist(3, 'Cleo')];
    $shortcuts = [];

    foreach ([['plain', 'about', 'About'], ['spare', 'unattached', 'Spare'], ['www.example.com', '/blog/2026/a b', 'Blog']] as [$host, $path, $label]) {
        $shortcut = new Shortcut();
        $shortcut->setHost($host);
        $shortcut->setPath($path);
        $shortcut->setLabel($label);
        $shortcuts[] = $shortcut;
    }

    $venues = [new Venue(new Handle('north-hall'), 'North Hall'), new Venue(new Handle('south-hall'), 'South Hall')];
    $residencies = [];

    foreach ([[0, 'summer', 'Summer'], [1, 'winter', 'Winter']] as [$index, $season, $label]) {
        $residency = new Residency();
        $residency->setVenue($venues[$index]);
        $residency->setSeason($season);
        $residency->setLabel($label);
        $residencies[] = $residency;
    }

    $circuits = [];

    foreach ([['north', 'one', 'North one'], ['south', 'two', 'South two'], ['east', 'three', 'East three']] as [$region, $slot, $name]) {
        $circuit = new Circuit();
        $circuit->setRegion($region);
        $circuit->setSlot($slot);
        $circuit->setName($name);
        $circuits[] = $circuit;
    }

    $notes = [new ShortcutNote(1, 'Alpha', 1), new ShortcutNote(2, 'Beta', 2), new ShortcutNote(3, 'Gamma', 3)];
    $riders = [new ResidencyRider(1, 'Rider one', 1), new ResidencyRider(2, 'Rider two', 2), new ResidencyRider(3, 'Rider three', 3)];
    $stop = new CircuitStop(1, 'First stop', $circuits[0]);

    foreach ([...$artists, ...$shortcuts, ...$venues, ...$residencies, ...$circuits, ...$notes, ...$riders, $stop] as $entity) {
        $entityManager->persist($entity);
    }

    $shortcuts[0]->addTroupe($artists[0]);
    $shortcuts[1]->addTroupe($artists[2]);
    $artists[1]->addShortcut($shortcuts[0]);
    $residencies[0]->addBooker($artists[0]);
    $circuits[0]->addShortcut($shortcuts[0]);
    $circuits[0]->addChild($circuits[1]);

    $shortcuts[0]->addNote($notes[0]);
    $shortcuts[1]->addNote($notes[2]);
    $residencies[0]->addRider($riders[0]);
    $residencies[1]->addRider($riders[2]);

    $entityManager->flush();
    $recorder->reset();

    return [$client, $entityManager, $recorder];
}

/**
 * @param class-string<CompositeOwnerRelationshipActionsManyToManyTable|CompositeOwnerRelationshipActionsOneToManyTable> $hostClass
 * @param array<string, mixed> $props
 */
function compositeOwnerActionsHost(KernelBrowser $client, string $hostClass, array $props = []): CompositeOwnerRelationshipActionsManyToManyTable|CompositeOwnerRelationshipActionsOneToManyTable
{
    return test()->createLiveComponent($hostClass, $props, client: $client)->component();
}

/**
 * The keys one owner's association holds, read back against a cold identity map
 * so a stale collection cannot answer for the database.
 *
 * @param class-string<CompositeOwnerRelationshipActionsManyToManyTable|CompositeOwnerRelationshipActionsOneToManyTable> $hostClass
 * @param array<string, mixed> $props
 *
 * @return list<string>
 */
function compositeOwnerActionsRelatedKeys(KernelBrowser $client, EntityManagerInterface $entityManager, string $hostClass, array $props = []): array
{
    $entityManager->clear();

    return array_keys(compositeOwnerActionsHost($client, $hostClass, $props)->getTable()->getRecords());
}

/**
 * The record select of a mounted attach or associate modal.
 *
 * @param class-string<CompositeOwnerRelationshipActionsManyToManyTable|CompositeOwnerRelationshipActionsOneToManyTable> $hostClass
 * @param array<string, mixed> $props
 */
function compositeOwnerActionsSelect(KernelBrowser $client, string $hostClass, string $actionName, array $props = []): Select
{
    $host = compositeOwnerActionsHost($client, $hostClass, $props);
    $host->mountAction($actionName, [], ['table' => true]);

    $select = $host->getMountedActionSchema()?->getComponent('recordId');

    return $select instanceof Select ? $select : throw new LogicException("The [{$actionName}] modal carries no record select.");
}

/**
 * Mount an attach or associate modal, fill the key in and submit it.
 *
 * @param class-string<CompositeOwnerRelationshipActionsManyToManyTable|CompositeOwnerRelationshipActionsOneToManyTable> $hostClass
 * @param array<string, mixed> $props
 */
function compositeOwnerActionsSubmitKey(KernelBrowser $client, string $hostClass, string $actionName, string $key, array $props = []): void
{
    $host = compositeOwnerActionsHost($client, $hostClass, $props);
    $host->mountAction($actionName, [], ['table' => true]);
    $host->getMountedActionSchema()?->fill(['recordId' => $key]);
    $host->callMountedAction();
}

/**
 * A record action against one row of the relation-scoped table.
 *
 * @param class-string<CompositeOwnerRelationshipActionsManyToManyTable|CompositeOwnerRelationshipActionsOneToManyTable> $hostClass
 * @param array<string, mixed> $props
 */
function compositeOwnerActionsCallOnRecord(KernelBrowser $client, string $hostClass, string $actionName, string $recordKey, array $props = []): CompositeOwnerRelationshipActionsManyToManyTable|CompositeOwnerRelationshipActionsOneToManyTable
{
    $host = compositeOwnerActionsHost($client, $hostClass, $props);
    $host->mountAction($actionName, [], ['table' => true, 'recordKey' => $recordKey]);
    $host->callMountedAction();

    return $host;
}

/**
 * A confirmed bulk action over a selection, as two live requests: the recorder
 * is reset between the mount and the call, so the statement list starts where
 * the action body does.
 *
 * @param class-string<CompositeOwnerRelationshipActionsManyToManyTable|CompositeOwnerRelationshipActionsOneToManyTable> $hostClass
 * @param array<string, mixed> $props
 * @param list<string> $selection
 *
 * @return list<string>
 */
function compositeOwnerActionsBulkStatements(KernelBrowser $client, RecordingMiddleware $recorder, string $hostClass, string $actionName, array $props, array $selection): array
{
    $component = test()->createLiveComponent($hostClass, $props, client: $client)
        ->set('selectedTableRecords', $selection)
        ->call('mountAction', ['name' => $actionName, 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]]);

    $recorder->reset();
    $component->call('callMountedAction');

    return $recorder->statements();
}

/**
 * The same shape for an arm expected to refuse: the throw is returned rather
 * than swallowed, so a case can assert the message *and* what the database
 * still holds.
 *
 * @param array<string, mixed> $props
 * @param list<string> $selection
 */
function compositeOwnerActionsBulkThrow(KernelBrowser $client, RecordingMiddleware $recorder, array $props, array $selection): LogicException
{
    $component = test()->createLiveComponent(CompositeOwnerRelationshipActionsOneToManyTable::class, $props, client: $client)
        ->set('selectedTableRecords', $selection)
        ->call('mountAction', ['name' => 'dissociate', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]]);

    $recorder->reset();

    try {
        $component->call('callMountedAction');
    } catch (LogicException $logicException) {
        return $logicException;
    }

    throw new LogicException('The by-key dissociate was expected to refuse and did not.');
}

/**
 * The foreign-key columns one child row stores, read straight off the
 * connection — the only reading that a detached collection and a stale identity
 * map cannot both answer for.
 *
 * @param list<string> $columns
 *
 * @return array<string, mixed>
 */
function compositeOwnerActionsStoredColumns(EntityManagerInterface $entityManager, string $table, int $id, array $columns): array
{
    $row = $entityManager->getConnection()->fetchAssociative(sprintf('SELECT %s FROM %s WHERE id = ?', implode(', ', $columns), $table), [$id]);

    return $row === false ? [] : $row;
}

/**
 * @param list<string> $statements
 *
 * @return list<string>
 */
function compositeOwnerActionsWrites(array $statements, string $verb): array
{
    return array_values(array_filter($statements, static fn (string $sql): bool => str_starts_with($sql, $verb)));
}

/**
 * @return list<string>
 */
function compositeOwnerActionsNotificationTitles(): array
{
    return array_map(
        static fn (array $notification): string => is_string($notification['title'] ?? null) ? $notification['title'] : '',
        test()->getContainer()->get(SessionNotifications::class)->pull(),
    );
}

it('refuses the by-key dissociate when the mapped-by spans the whole identifier of the owner', function (): void {
    [$client, , $recorder] = compositeOwnerActionsSeed();

    expect(compositeOwnerActionsBulkThrow($client, $recorder, ['fetchesRecords' => false], ['1'])->getMessage())
        ->toBe('[' . ShortcutNote::class . '::$shortcut] spans 2 join columns, and DQL cannot null a to-one that spans more than one, so the action [dissociate] cannot dissociate by key here — drop fetchSelectedRecords(false) so the records are loaded and dissociated through the mapping.');
});

it('refuses the same arm under a derived-identity owner, naming that mapping instead', function (): void {
    [$client, , $recorder] = compositeOwnerActionsSeed();

    expect(compositeOwnerActionsBulkThrow($client, $recorder, ['owner' => 'residency', 'association' => 'riders', 'fetchesRecords' => false], ['1'])->getMessage())
        ->toBe('[' . ResidencyRider::class . '::$residency] spans 2 join columns, and DQL cannot null a to-one that spans more than one, so the action [dissociate] cannot dissociate by key here — drop fetchSelectedRecords(false) so the records are loaded and dissociated through the mapping.');
});

it('refuses before any write and before any toast, leaving the stored pair intact', function (): void {
    [$client, $entityManager, $recorder] = compositeOwnerActionsSeed();

    $refusal = compositeOwnerActionsBulkThrow($client, $recorder, ['fetchesRecords' => false], ['1']);

    expect($refusal->getMessage())->toContain('cannot dissociate by key here')
        ->and(compositeOwnerActionsWrites($recorder->statements(), 'UPDATE'))->toBe([])
        ->and(compositeOwnerActionsNotificationTitles())->toBe([])
        ->and(compositeOwnerActionsStoredColumns($entityManager, 'ShortcutNote', 1, ['shortcut_host', 'shortcut_path']))
        ->toBe(['shortcut_host' => 'plain', 'shortcut_path' => 'about']);
});

it('dissociates the same collection through the fetching arm, nulling the stored pair', function (): void {
    [$client, $entityManager, $recorder] = compositeOwnerActionsSeed();

    $statements = compositeOwnerActionsBulkStatements($client, $recorder, CompositeOwnerRelationshipActionsOneToManyTable::class, 'dissociate', ['fetchesRecords' => true], ['1']);

    expect(compositeOwnerActionsWrites($statements, 'UPDATE'))->toHaveCount(1)
        ->and(compositeOwnerActionsStoredColumns($entityManager, 'ShortcutNote', 1, ['shortcut_host', 'shortcut_path']))
        ->toBe(['shortcut_host' => null, 'shortcut_path' => null])
        ->and(compositeOwnerActionsRelatedKeys($client, $entityManager, CompositeOwnerRelationshipActionsOneToManyTable::class))->toBe([]);
});

it('dissociates a derived-identity owner collection through the fetching arm', function (): void {
    [$client, $entityManager, $recorder] = compositeOwnerActionsSeed();
    $props = ['owner' => 'residency', 'association' => 'riders', 'fetchesRecords' => true];

    compositeOwnerActionsBulkStatements($client, $recorder, CompositeOwnerRelationshipActionsOneToManyTable::class, 'dissociate', $props, ['1']);

    expect(compositeOwnerActionsStoredColumns($entityManager, 'ResidencyRider', 1, ['residency_venue_id', 'residency_season']))
        ->toBe(['residency_venue_id' => null, 'residency_season' => null])
        ->and(compositeOwnerActionsRelatedKeys($client, $entityManager, CompositeOwnerRelationshipActionsOneToManyTable::class, $props))->toBe([]);
});

it('scopes the many-to-many to this owner rather than to every owner of one', function (): void {
    [$client, $entityManager] = compositeOwnerActionsSeed();

    // `spare~unattached` holds Cleo in the same association, so a correlated
    // `EXISTS` that lost its owner clause would list her here and drop her from
    // the candidates in the same breath.
    expect(compositeOwnerActionsRelatedKeys($client, $entityManager, CompositeOwnerRelationshipActionsManyToManyTable::class))->toBe([1])
        ->and(array_keys(compositeOwnerActionsSelect($client, CompositeOwnerRelationshipActionsManyToManyTable::class, 'attach')->getSearchResults('')))->toBe([2, 3]);
});

it('offers a composite owner unattached candidates and searches them', function (): void {
    [$client] = compositeOwnerActionsSeed();

    $select = compositeOwnerActionsSelect($client, CompositeOwnerRelationshipActionsManyToManyTable::class, 'attach');

    expect($select->getSearchResults(''))->toBe(['2' => 'Bob', '3' => 'Cleo'])
        ->and($select->getSearchResults('Cle'))->toBe(['3' => 'Cleo']);
});

it('offers candidates through the inverse side of the many-to-many', function (): void {
    [$client] = compositeOwnerActionsSeed();

    // `Artist::$shortcuts` owns the join table, so this collection is scoped
    // through the owner's own association rather than through any mapped-by
    // field of the target.
    expect(compositeOwnerActionsSelect($client, CompositeOwnerRelationshipActionsManyToManyTable::class, 'attach', ['association' => 'billedBy'])->getSearchResults(''))
        ->toBe(['1' => 'Ada', '3' => 'Cleo']);
});

it('offers candidates for a derived-identity owner', function (): void {
    [$client] = compositeOwnerActionsSeed();

    expect(compositeOwnerActionsSelect($client, CompositeOwnerRelationshipActionsManyToManyTable::class, 'attach', ['owner' => 'residency', 'association' => 'bookers'])->getSearchResults(''))
        ->toBe(['2' => 'Bob', '3' => 'Cleo']);
});

it('offers composite candidates when owner and target are both composite', function (): void {
    [$client] = compositeOwnerActionsSeed();

    expect(compositeOwnerActionsSelect($client, CompositeOwnerRelationshipActionsManyToManyTable::class, 'attach', ['owner' => 'circuit', 'association' => 'shortcuts'])->getSearchResults(''))
        ->toBe([
            'www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b' => 'Blog',
            'spare~unattached' => 'Spare',
        ]);
});

it('attaches a single-identifier target into a composite owner', function (): void {
    [$client, $entityManager] = compositeOwnerActionsSeed();

    compositeOwnerActionsSubmitKey($client, CompositeOwnerRelationshipActionsManyToManyTable::class, 'attach', '2');

    expect(compositeOwnerActionsRelatedKeys($client, $entityManager, CompositeOwnerRelationshipActionsManyToManyTable::class))->toBe([1, 2]);
});

it('attaches a composite target into a composite owner', function (): void {
    [$client, $entityManager] = compositeOwnerActionsSeed();
    $props = ['owner' => 'circuit', 'association' => 'shortcuts'];

    compositeOwnerActionsSubmitKey($client, CompositeOwnerRelationshipActionsManyToManyTable::class, 'attach', 'spare~unattached', $props);

    expect(compositeOwnerActionsRelatedKeys($client, $entityManager, CompositeOwnerRelationshipActionsManyToManyTable::class, $props))
        ->toBe(['plain~about', 'spare~unattached']);
});

it('attaches through the inverse side, writing the join row from the target end', function (): void {
    [$client, $entityManager] = compositeOwnerActionsSeed();
    $props = ['association' => 'billedBy'];

    compositeOwnerActionsSubmitKey($client, CompositeOwnerRelationshipActionsManyToManyTable::class, 'attach', '1', $props);

    expect(compositeOwnerActionsRelatedKeys($client, $entityManager, CompositeOwnerRelationshipActionsManyToManyTable::class, $props))->toBe([1, 2]);
});

it('detaches one record from a composite owner', function (): void {
    [$client, $entityManager] = compositeOwnerActionsSeed();

    compositeOwnerActionsCallOnRecord($client, CompositeOwnerRelationshipActionsManyToManyTable::class, 'detach', '1');

    expect(compositeOwnerActionsRelatedKeys($client, $entityManager, CompositeOwnerRelationshipActionsManyToManyTable::class))->toBe([]);
});

it('detaches one record through the inverse side', function (): void {
    [$client, $entityManager] = compositeOwnerActionsSeed();
    $props = ['association' => 'billedBy'];

    compositeOwnerActionsCallOnRecord($client, CompositeOwnerRelationshipActionsManyToManyTable::class, 'detach', '2', $props);

    expect(compositeOwnerActionsRelatedKeys($client, $entityManager, CompositeOwnerRelationshipActionsManyToManyTable::class, $props))->toBe([]);
});

it('detaches a selection by key from a composite owner without fetching the rows', function (): void {
    [$client, $entityManager, $recorder] = compositeOwnerActionsSeed();

    $statements = compositeOwnerActionsBulkStatements($client, $recorder, CompositeOwnerRelationshipActionsManyToManyTable::class, 'detach', ['fetchesRecords' => false], ['1']);

    expect(compositeOwnerActionsWrites($statements, 'DELETE'))->toHaveCount(1)
        ->and(compositeOwnerActionsRelatedKeys($client, $entityManager, CompositeOwnerRelationshipActionsManyToManyTable::class))->toBe([]);
});

it('detaches the same selection through the fetching arm', function (): void {
    [$client, $entityManager, $recorder] = compositeOwnerActionsSeed();

    compositeOwnerActionsBulkStatements($client, $recorder, CompositeOwnerRelationshipActionsManyToManyTable::class, 'detach', ['fetchesRecords' => true], ['1']);

    expect(compositeOwnerActionsRelatedKeys($client, $entityManager, CompositeOwnerRelationshipActionsManyToManyTable::class))->toBe([]);
});

it('detaches a composite target by key from a composite owner', function (): void {
    [$client, $entityManager, $recorder] = compositeOwnerActionsSeed();
    $props = ['owner' => 'circuit', 'association' => 'shortcuts', 'fetchesRecords' => false];

    compositeOwnerActionsBulkStatements($client, $recorder, CompositeOwnerRelationshipActionsManyToManyTable::class, 'detach', $props, ['plain~about']);

    expect(compositeOwnerActionsRelatedKeys($client, $entityManager, CompositeOwnerRelationshipActionsManyToManyTable::class, $props))->toBe([]);
});

it('offers the records nobody owns and the records of another owner, never the records of this owner', function (): void {
    [$client] = compositeOwnerActionsSeed();

    // `Beta` carries a NULL foreign-key pair and `Gamma` belongs to the other
    // shortcut; `Alpha` is this owner's and is the one the subselect excludes.
    expect(compositeOwnerActionsSelect($client, CompositeOwnerRelationshipActionsOneToManyTable::class, 'associate')->getSearchResults(''))
        ->toBe(['2' => 'Beta', '3' => 'Gamma']);
});

it('offers the same candidates for a derived-identity owner', function (): void {
    [$client] = compositeOwnerActionsSeed();

    expect(compositeOwnerActionsSelect($client, CompositeOwnerRelationshipActionsOneToManyTable::class, 'associate', ['owner' => 'residency', 'association' => 'riders'])->getSearchResults(''))
        ->toBe(['3' => 'Rider three', '2' => 'Rider two']);
});

it('offers a composite target candidates, this owner among them', function (): void {
    [$client] = compositeOwnerActionsSeed();

    // A record is never its own child, so the owner itself is a candidate for
    // its own collection — what `NOT EXISTS` answers and `<> :owner` could not.
    expect(compositeOwnerActionsSelect($client, CompositeOwnerRelationshipActionsOneToManyTable::class, 'associate', ['owner' => 'circuit', 'association' => 'children'])->getSearchResults(''))
        ->toBe(['east~three' => 'East three', 'north~one' => 'North one']);
});

it('associates a record into a composite owner, writing both join columns', function (): void {
    [$client, $entityManager] = compositeOwnerActionsSeed();

    compositeOwnerActionsSubmitKey($client, CompositeOwnerRelationshipActionsOneToManyTable::class, 'associate', '2');

    expect(compositeOwnerActionsStoredColumns($entityManager, 'ShortcutNote', 2, ['shortcut_host', 'shortcut_path']))
        ->toBe(['shortcut_host' => 'plain', 'shortcut_path' => 'about'])
        ->and(compositeOwnerActionsRelatedKeys($client, $entityManager, CompositeOwnerRelationshipActionsOneToManyTable::class))->toBe([1, 2]);
});

it('associates a record into a derived-identity owner', function (): void {
    [$client, $entityManager] = compositeOwnerActionsSeed();
    $props = ['owner' => 'residency', 'association' => 'riders'];

    compositeOwnerActionsSubmitKey($client, CompositeOwnerRelationshipActionsOneToManyTable::class, 'associate', '2', $props);

    expect(compositeOwnerActionsStoredColumns($entityManager, 'ResidencyRider', 2, ['residency_venue_id', 'residency_season']))
        ->toBe(['residency_venue_id' => 'north-hall', 'residency_season' => 'summer'])
        ->and(compositeOwnerActionsRelatedKeys($client, $entityManager, CompositeOwnerRelationshipActionsOneToManyTable::class, $props))->toBe([1, 2]);
});

it('dissociates one record from a composite owner, nulling both join columns', function (): void {
    [$client, $entityManager] = compositeOwnerActionsSeed();

    compositeOwnerActionsCallOnRecord($client, CompositeOwnerRelationshipActionsOneToManyTable::class, 'dissociate', '1');

    expect(compositeOwnerActionsStoredColumns($entityManager, 'ShortcutNote', 1, ['shortcut_host', 'shortcut_path']))
        ->toBe(['shortcut_host' => null, 'shortcut_path' => null])
        ->and(compositeOwnerActionsRelatedKeys($client, $entityManager, CompositeOwnerRelationshipActionsOneToManyTable::class))->toBe([]);
});

it('refuses to dissociate a collection whose join columns are NOT NULL', function (): void {
    [$client] = compositeOwnerActionsSeed();

    expect(fn (): object => compositeOwnerActionsCallOnRecord($client, CompositeOwnerRelationshipActionsOneToManyTable::class, 'dissociate', '1', ['owner' => 'circuit', 'association' => 'stops']))
        ->toThrow(LogicException::class, 'is mapped with a `nullable: false` join column');
});
