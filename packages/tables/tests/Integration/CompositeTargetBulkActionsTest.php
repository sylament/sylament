<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Notifications\Delivery\SessionNotifications;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Locker;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\LockerLease;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\LockerSeal;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;
use Sylament\Tables\Tests\Fixtures\Doctrine\RecordingMiddleware;
use Sylament\Tables\Tests\Fixtures\Twig\Components\ArtistCuratedShortcutsBulkTable;
use Sylament\Tables\Tests\Fixtures\Twig\Components\ArtistKeptLockersBulkTable;
use Sylament\Tables\Tests\Fixtures\Twig\Components\ArtistShortcutsBulkTable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

uses(InteractsWithLiveComponents::class);

/*
 * `DetachBulkAction` and `DissociateBulkAction` over a relationship whose
 * target is keyed by more than one field, by an association plus a scalar, or
 * by an association alone. It sits in the **tables** suite for the reason
 * `AttachDetachActionsTest` does: only a table host supplies a relationship and
 * a selection, and the actions test kernel registers neither the tables nor the
 * forms bundle.
 *
 * The by-key cases are the interesting ones. A selection reaches either action
 * as the keys the client sent, and what the action does with them — hand one to
 * a reference, or bind the whole chunk into a DQL `UPDATE` — is what a target
 * wider than one `id` column breaks. Every expected key is a string literal:
 * asking the codec for the expectation would pass against any format at all.
 *
 * Cases that need writable selection props run as real live requests with
 * reboot disabled; the ones whose host answers the selection itself run
 * in-process on one host object, where the toast is a session flash this file's
 * own request carries.
 */

beforeEach(function (): void {
    $request = new Request();
    $request->setSession($this::getContainer()->get('session.factory')->createSession());
    $this::getContainer()->get(RequestStack::class)->push($request);
});

/**
 * Two artists and one record of every flavour a target can take, with Ada
 * holding enough of each that a detach has something to remove and a
 * dissociate something to null. The identity map is left intact, the way
 * `seededGenresClient()` seeds.
 *
 * @return array{KernelBrowser, EntityManagerInterface, RecordingMiddleware}
 */
function compositeBulkSeed(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $recorder = test()->getContainer()->get(RecordingMiddleware::class);

    $ada = new Artist(1, 'Ada');
    $bob = new Artist(2, 'Bob');

    $shortcuts = [];

    foreach ([['plain', 'about', 'About'], ['www.example.com', '/blog/2026/a b', 'Blog'], ['spare', 'unattached', 'Spare']] as [$host, $path, $label]) {
        $shortcut = new Shortcut();
        $shortcut->setHost($host);
        $shortcut->setPath($path);
        $shortcut->setLabel($label);
        $shortcuts[] = $shortcut;
    }

    $venues = [new Venue(new Handle('north-hall'), 'North Hall'), new Venue(new Handle('south-hall'), 'South Hall')];
    $residencies = [];

    foreach ([[0, 'summer', 'Summer'], [1, 'winter 26', 'Winter']] as [$index, $season, $label]) {
        $residency = new Residency();
        $residency->setVenue($venues[$index]);
        $residency->setSeason($season);
        $residency->setLabel($label);
        $residencies[] = $residency;
    }

    $lockers = [new Locker('NORTH-HALL', 'North locker'), new Locker('SOUTH-HALL', 'South locker')];
    $seal = new LockerSeal($lockers[0], 'North seal', 1);

    $lease = new LockerLease();
    $lease->setLocker($lockers[0]);
    $lease->setArtist($ada);
    $lease->setLabel('North lease');

    foreach ([$ada, $bob, ...$shortcuts, ...$venues, ...$residencies, ...$lockers, $seal, $lease] as $entity) {
        $entityManager->persist($entity);
    }

    $ada->addShortcut($shortcuts[0])->addShortcut($shortcuts[1]);
    $bob->addShortcut($shortcuts[0]);
    $shortcuts[0]->addTroupe($bob);
    $shortcuts[1]->addTroupe($ada);

    $ada->addResidency($residencies[0]);
    $bob->addResidency($residencies[1]);
    $residencies[0]->addBooker($bob);

    $ada->addSeal($seal);
    $ada->addVenue($venues[0])->addVenue($venues[1]);

    $lockers[0]->setKeeper($ada);
    $lockers[1]->setKeeper($ada);

    $shortcuts[0]->setCurator($ada);
    $shortcuts[1]->setCurator($bob);

    $entityManager->flush();
    $recorder->reset();

    return [$client, $entityManager, $recorder];
}

/**
 * The keys one owner's association holds, read back against a cold identity map
 * so a stale collection cannot answer for the database.
 *
 * @param array<string, mixed> $props
 *
 * @return list<string>
 */
function compositeBulkOwnedKeys(KernelBrowser $client, EntityManagerInterface $entityManager, string $host, array $props = []): array
{
    $entityManager->clear();

    /** @var class-string<ArtistCuratedShortcutsBulkTable|ArtistKeptLockersBulkTable|ArtistShortcutsBulkTable> $host */
    return array_keys(test()->createLiveComponent($host, $props, client: $client)->component()->getTable()->getRecords());
}

/**
 * The confirmed bulk action's *second* request alone: the recorder is reset
 * between the mount and the call, so the statement list starts where the action
 * body does. `$coldEntityManager` empties the identity map in that same gap —
 * what a by-key case needs, since a record the seed left managed is handed back
 * by a reference that never had to build one.
 *
 * @param array<string, mixed> $props
 * @param list<string> $selection
 *
 * @return list<string>
 */
function compositeBulkStatements(KernelBrowser $client, RecordingMiddleware $recorder, string $host, string $action, array $props, array $selection, ?EntityManagerInterface $coldEntityManager = null): array
{
    /** @var class-string<ArtistCuratedShortcutsBulkTable|ArtistKeptLockersBulkTable|ArtistShortcutsBulkTable> $host */
    $component = test()->createLiveComponent($host, $props, client: $client)
        ->set('selectedTableRecords', $selection)
        ->call('mountAction', ['name' => $action, 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]]);

    $coldEntityManager?->clear();
    $recorder->reset();
    $component->call('callMountedAction');

    return $recorder->statements();
}

/**
 * The bulk action run in-process on the host that answers its own selection —
 * the route the shape refusals and the stale key need, since nothing the
 * table's own read can produce reaches them.
 *
 * @param array<string, mixed> $props
 */
function compositeBulkCallOnOverridingHost(KernelBrowser $client, string $action, array $props): ArtistCuratedShortcutsBulkTable
{
    $host = test()->createLiveComponent(ArtistCuratedShortcutsBulkTable::class, $props, client: $client)->component();
    $host->mountAction($action, [], ['table' => true, 'bulk' => true]);
    $host->callMountedAction();

    return $host;
}

/**
 * @return list<string>
 */
function compositeBulkNotificationTitles(): array
{
    return array_map(
        static fn (array $notification): string => is_string($notification['title'] ?? null) ? $notification['title'] : '',
        test()->getContainer()->get(SessionNotifications::class)->pull(),
    );
}

/**
 * The statements that both read a shortcut's own columns and narrow by the
 * selected keys — a hydrating selection. The by-key arm plucks the same keys,
 * but its projection is the identifier alone, so only the fetching arm has one;
 * the re-render that follows either arm reads the columns without binding a
 * host at all.
 *
 * @param list<string> $statements
 *
 * @return list<string>
 */
function compositeBulkHydratingSelections(array $statements): array
{
    return array_values(array_filter(
        $statements,
        static fn (string $sql): bool => str_contains($sql, 'label_') && str_contains($sql, '.host = ?'),
    ));
}

/**
 * @param list<string> $statements
 *
 * @return list<string>
 */
function compositeBulkWrites(array $statements, string $verb): array
{
    return array_values(array_filter($statements, static fn (string $sql): bool => str_starts_with($sql, $verb)));
}

it('detaches a scalar-pair selection by key without ever selecting the rows', function (): void {
    [$client, $entityManager, $recorder] = compositeBulkSeed();

    $statements = compositeBulkStatements($client, $recorder, ArtistShortcutsBulkTable::class, 'detach', ['fetchesRecords' => false], ['plain~about'], coldEntityManager: $entityManager);

    expect(compositeBulkOwnedKeys($client, $entityManager, ArtistShortcutsBulkTable::class))->toBe(['www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b'])
        ->and(compositeBulkHydratingSelections($statements))->toBe([]);
});

it('detaches the same selection through the fetching arm, which is the contrast', function (): void {
    [$client, $entityManager, $recorder] = compositeBulkSeed();

    $statements = compositeBulkStatements($client, $recorder, ArtistShortcutsBulkTable::class, 'detach', ['fetchesRecords' => true], ['plain~about']);

    expect(compositeBulkOwnedKeys($client, $entityManager, ArtistShortcutsBulkTable::class))->toBe(['www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b'])
        ->and(compositeBulkHydratingSelections($statements))->toHaveCount(1);
});

it('detaches a derived-identity selection by key against a cold identity map', function (): void {
    [$client, $entityManager, $recorder] = compositeBulkSeed();
    $props = ['association' => 'residencies', 'fetchesRecords' => false];

    $component = test()->createLiveComponent(ArtistShortcutsBulkTable::class, $props, client: $client)
        ->set('selectedTableRecords', ['north-hall~summer'])
        ->call('mountAction', ['name' => 'detach', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]]);

    // A warm identity map hides the `getReference()` TypeError this route exists
    // to avoid, so the map is emptied before the action body runs.
    $entityManager->clear();
    $recorder->reset();
    $component->call('callMountedAction');

    expect(compositeBulkOwnedKeys($client, $entityManager, ArtistShortcutsBulkTable::class, $props))->toBe([]);
});

it('detaches a composite selection from the inverse side of the many-to-many', function (): void {
    [$client, $entityManager, $recorder] = compositeBulkSeed();
    $props = ['association' => 'billedShortcuts', 'fetchesRecords' => false];

    compositeBulkStatements($client, $recorder, ArtistShortcutsBulkTable::class, 'detach', $props, ['www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b'], coldEntityManager: $entityManager);

    expect(compositeBulkOwnedKeys($client, $entityManager, ArtistShortcutsBulkTable::class, $props))->toBe([]);
});

it('propagates a selection entry that is neither a record nor a key from the detach', function (): void {
    [$client] = compositeBulkSeed();

    expect(fn (): ArtistCuratedShortcutsBulkTable => compositeBulkCallOnOverridingHost($client, 'detach', ['selectionOverride' => 'rows']))
        ->toThrow(LogicException::class, 'DetachBulkAction can only detach entities or their keys, got array.');
});

it('propagates the same entry from the dissociate before the keys are chunked', function (): void {
    [$client] = compositeBulkSeed();

    expect(fn (): ArtistCuratedShortcutsBulkTable => compositeBulkCallOnOverridingHost($client, 'dissociate', ['selectionOverride' => 'rows']))
        ->toThrow(LogicException::class, 'DissociateBulkAction dissociates by key from record keys, got array.');
});

it('detaches an integer selection entry, which the walk before the try casts to a key', function (): void {
    [$client, $entityManager] = compositeBulkSeed();
    $props = ['association' => 'songs', 'selectionOverride' => 'int'];

    $ada = $entityManager->find(Artist::class, 1) ?? throw new LogicException('The rig lost its artist.');
    $song = new Song(7, 'Anthem');

    $entityManager->persist($song);
    $ada->addSong($song);
    $entityManager->flush();

    compositeBulkCallOnOverridingHost($client, 'detach', $props);

    expect(compositeBulkNotificationTitles())->toBe(['Detached'])
        ->and(compositeBulkOwnedKeys($client, $entityManager, ArtistCuratedShortcutsBulkTable::class, ['association' => 'songs']))->toBe([]);
});

it('propagates the same entry from the dissociate, which takes its keys as strings', function (): void {
    [$client] = compositeBulkSeed();

    expect(fn (): ArtistCuratedShortcutsBulkTable => compositeBulkCallOnOverridingHost($client, 'dissociate', ['selectionOverride' => 'int']))
        ->toThrow(LogicException::class, 'DissociateBulkAction dissociates by key from record keys, got int.');
});

it('reports a failure toast for a well-formed key naming no record', function (): void {
    [$client, $entityManager] = compositeBulkSeed();

    compositeBulkCallOnOverridingHost($client, 'detach', ['selectionOverride' => 'stale']);

    expect(compositeBulkNotificationTitles())->toBe(['Failed to detach 1 records'])
        ->and(compositeBulkOwnedKeys($client, $entityManager, ArtistCuratedShortcutsBulkTable::class))->toBe(['plain~about']);
});

it('reports the same toast for a key no record of the target could mint', function (): void {
    [$client, $entityManager] = compositeBulkSeed();

    compositeBulkCallOnOverridingHost($client, 'detach', ['selectionOverride' => 'malformed']);

    expect(compositeBulkNotificationTitles())->toBe(['Failed to detach 1 records'])
        ->and(compositeBulkOwnedKeys($client, $entityManager, ArtistCuratedShortcutsBulkTable::class))->toBe(['plain~about']);
});

it('reports the same toast for a stale key on a target the reference has to load', function (): void {
    [$client, $entityManager] = compositeBulkSeed();
    $props = ['association' => 'residencies', 'selectionOverride' => 'staleFind'];

    compositeBulkCallOnOverridingHost($client, 'detach', $props);

    expect(compositeBulkNotificationTitles())->toBe(['Failed to detach 1 records'])
        ->and(compositeBulkOwnedKeys($client, $entityManager, ArtistCuratedShortcutsBulkTable::class, ['association' => 'residencies']))->toBe(['north-hall~summer']);
});

it('dissociates a converting single identifier by key, where the raw bind used to match no row', function (): void {
    [$client, $entityManager, $recorder] = compositeBulkSeed();

    $statements = compositeBulkStatements($client, $recorder, ArtistKeptLockersBulkTable::class, 'dissociate', [], ['NORTH-HALL', 'SOUTH-HALL']);

    // The column holds `north-hall`/`south-hall` and the record mints
    // `NORTH-HALL`/`SOUTH-HALL`; the raw bind this arm used to carry matched no
    // row at all, and the action answered the danger toast
    // `Failed to dissociate 2 records`.
    expect(compositeBulkWrites($statements, 'UPDATE'))->toHaveCount(1)
        ->and(compositeBulkOwnedKeys($client, $entityManager, ArtistKeptLockersBulkTable::class))->toBe([])
        ->and($entityManager->find(Locker::class, 'NORTH-HALL')?->getKeeper())->toBeNull()
        ->and($entityManager->find(Locker::class, 'SOUTH-HALL')?->getKeeper())->toBeNull();
});

it('dissociates a scalar-pair selection through one update of ORed identifier pairs', function (): void {
    [$client, $entityManager, $recorder] = compositeBulkSeed();

    $spare = $entityManager->find(Shortcut::class, ['host' => 'spare', 'path' => 'unattached']) ?? throw new LogicException('The rig lost its spare shortcut.');
    $spare->setCurator($entityManager->find(Artist::class, 1));

    $entityManager->flush();

    $statements = compositeBulkStatements($client, $recorder, ArtistCuratedShortcutsBulkTable::class, 'dissociate', [], ['plain~about', 'spare~unattached']);
    $updates = compositeBulkWrites($statements, 'UPDATE');

    expect($updates)->toHaveCount(1)
        ->and($updates[0])->toContain(' OR ')
        ->and($updates[0])->toContain('host = ?')
        ->and($updates[0])->toContain('path = ?')
        ->and(compositeBulkWrites($statements, 'BEGIN'))->toBe([])
        ->and(compositeBulkOwnedKeys($client, $entityManager, ArtistCuratedShortcutsBulkTable::class))->toBe([]);
});

it('dissociates a selection past the chunk size in one transaction of two updates', function (): void {
    [$client, $entityManager, $recorder] = compositeBulkSeed();

    $ada = $entityManager->find(Artist::class, 1) ?? throw new LogicException('The rig lost its artist.');
    $keys = ['plain~about'];

    for ($index = 0; $index < 500; $index++) {
        $shortcut = new Shortcut();
        $shortcut->setHost('bulk');
        $shortcut->setPath("p{$index}");
        $shortcut->setLabel("Bulk {$index}");
        $shortcut->setCurator($ada);
        $entityManager->persist($shortcut);

        $keys[] = "bulk~p{$index}";
    }

    $entityManager->flush();

    $statements = compositeBulkStatements($client, $recorder, ArtistCuratedShortcutsBulkTable::class, 'dissociate', [], $keys);
    $written = array_values(array_filter(
        $statements,
        static fn (string $sql): bool => in_array($sql, ['BEGIN', 'COMMIT'], true) || str_starts_with($sql, 'UPDATE'),
    ));

    expect($written)->toHaveCount(4)
        ->and($written[0])->toBe('BEGIN')
        ->and($written[1])->toStartWith('UPDATE')
        ->and($written[2])->toStartWith('UPDATE')
        ->and($written[3])->toBe('COMMIT')
        ->and(compositeBulkOwnedKeys($client, $entityManager, ArtistCuratedShortcutsBulkTable::class))->toBe([]);
});

it('writes nothing for an empty by-key selection, which the chunker answers with no chunk at all', function (): void {
    [$client, , $recorder] = compositeBulkSeed();

    $component = test()->createLiveComponent(ArtistKeptLockersBulkTable::class, [], client: $client)
        ->call('mountAction', ['name' => 'dissociate', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]]);

    $recorder->reset();
    $html = (string) $component->call('callMountedAction')->render();

    expect(compositeBulkWrites($recorder->statements(), 'UPDATE'))->toBe([])
        ->and($html)->not->toContain('Failed to dissociate');
});

it('refuses to dissociate a relationship whose mapped-by is part of the target identifier', function (): void {
    [$client, , $recorder] = compositeBulkSeed();
    $props = ['ownerClass' => 'locker', 'ownerKey' => 'NORTH-HALL', 'association' => 'leases', 'fetchesRecords' => true];

    $component = test()->createLiveComponent(ArtistKeptLockersBulkTable::class, $props, client: $client)
        ->set('selectedTableRecords', ['NORTH-HALL~1'])
        ->call('mountAction', ['name' => 'dissociate', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]]);

    $recorder->reset();

    expect(fn (): TestLiveComponent => $component->call('callMountedAction'))
        ->toThrow(LogicException::class, 'is part of the identifier of')
        ->and(compositeBulkWrites($recorder->statements(), 'UPDATE'))->toBe([]);
});

it('refuses the same relationship on the by-key arm, before any statement', function (): void {
    [$client, , $recorder] = compositeBulkSeed();
    $props = ['ownerClass' => 'locker', 'ownerKey' => 'NORTH-HALL', 'association' => 'leases', 'fetchesRecords' => false];

    $component = test()->createLiveComponent(ArtistKeptLockersBulkTable::class, $props, client: $client)
        ->set('selectedTableRecords', ['NORTH-HALL~1'])
        ->call('mountAction', ['name' => 'dissociate', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]]);

    $recorder->reset();

    expect(fn (): TestLiveComponent => $component->call('callMountedAction'))
        ->toThrow(LogicException::class, 'is part of the identifier of')
        ->and(compositeBulkWrites($recorder->statements(), 'UPDATE'))->toBe([]);
});
