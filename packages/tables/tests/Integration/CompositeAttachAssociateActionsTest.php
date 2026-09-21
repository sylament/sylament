<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\Select;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Locker;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\LockerLease;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\LockerSeal;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;
use Sylament\Tables\Tests\Fixtures\Doctrine\RecordingMiddleware;
use Sylament\Tables\Tests\Fixtures\Twig\Components\ArtistAssociateLeasesTable;
use Sylament\Tables\Tests\Fixtures\Twig\Components\ArtistAssociateLockersTable;
use Sylament\Tables\Tests\Fixtures\Twig\Components\ArtistAssociateShortcutsTable;
use Sylament\Tables\Tests\Fixtures\Twig\Components\ArtistResidenciesAttachTable;
use Sylament\Tables\Tests\Fixtures\Twig\Components\ArtistSealsAttachTable;
use Sylament\Tables\Tests\Fixtures\Twig\Components\ArtistShortcutsAttachTable;
use Sylament\Tables\Tests\Fixtures\Twig\Components\ArtistVenuesAttachTable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * `AttachAction` and `AssociateAction` over a relation whose **target** is keyed
 * by more than one field, by an association plus a scalar, by one association
 * alone, or by a value object. It sits in the tables suite for the reason
 * `AttachDetachActionsTest` does: only a table host supplies a relationship, and
 * the actions test kernel registers neither the tables nor the forms bundle, so
 * neither a relation-scoped table nor the record select could be built there.
 *
 * Every expected key is a literal. Minting one through `RecordKey` would make
 * the assertion agree with whatever format the codec happens to print.
 */

beforeEach(function (): void {
    $request = new Request();
    $request->setSession($this::getContainer()->get('session.factory')->createSession());
    $this::getContainer()->get(RequestStack::class)->push($request);
});

/**
 * Two artists and one record of every identifier flavour, with Ada already
 * holding the first of each — the first locker through its ordinary nullable
 * keeper column — so every candidate query has something to exclude.
 * Seeded through the container's own manager, identity map left intact, the way
 * {@see seededGenresClient()} seeds.
 *
 * @return array{KernelBrowser, EntityManagerInterface, RecordingMiddleware}
 */
function compositeAttachSeededClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $recorder = test()->getContainer()->get(RecordingMiddleware::class);

    $artists = [new Artist(1, 'Ada'), new Artist(2, 'Bob')];
    $shortcuts = [];

    foreach ([['plain', 'about', 'About'], ['www.example.com', '/blog/2026/a b', 'Blog'], ['plain', 'contact', 'Contact']] as [$host, $path, $label]) {
        $shortcut = new Shortcut();
        $shortcut->setHost($host);
        $shortcut->setPath($path);
        $shortcut->setLabel($label);
        $shortcuts[] = $shortcut;
    }

    $venues = [new Venue(new Handle('main-hall'), 'Main Hall'), new Venue(new Handle('side-room'), 'Side Room')];
    $residencies = [];

    foreach ([[0, 'Main 2026'], [1, 'Side 2026']] as [$index, $label]) {
        $residency = new Residency();
        $residency->setVenue($venues[$index]);
        $residency->setSeason('2026');
        $residency->setLabel($label);
        $residencies[] = $residency;
    }

    $lockers = [new Locker('L1', 'Locker one'), new Locker('L2', 'Locker two')];
    $seals = [new LockerSeal($lockers[0], 'Seal one', 1), new LockerSeal($lockers[1], 'Seal two', 2)];

    $lease = new LockerLease();
    $lease->setLocker($lockers[0]);
    $lease->setArtist($artists[1]);
    $lease->setLabel('Lease one');

    foreach ([...$artists, ...$shortcuts, ...$venues, ...$residencies, ...$lockers, ...$seals, $lease] as $entity) {
        $entityManager->persist($entity);
    }

    $artists[0]->addShortcut($shortcuts[0]);
    $artists[0]->addResidency($residencies[0]);
    $artists[0]->addSeal($seals[0]);
    $artists[0]->addVenue($venues[0]);
    $shortcuts[0]->addTroupe($artists[0]);
    $shortcuts[0]->setCurator($artists[1]);
    $lockers[0]->setKeeper($artists[0]);

    $entityManager->flush();
    $recorder->reset();

    return [$client, $entityManager, $recorder];
}

/**
 * @param class-string $hostClass
 * @param array<string, mixed> $props
 */
function compositeAttachHost(KernelBrowser $client, string $hostClass, array $props = []): InteractsWithActions&InteractsWithTable
{
    $host = test()->createLiveComponent($hostClass, $props, client: $client)->component();

    return $host instanceof InteractsWithActions && $host instanceof InteractsWithTable
        ? $host
        : throw new LogicException("[{$hostClass}] is not an action-hosting table.");
}

/**
 * The record select of a mounted attach or associate modal.
 *
 * @param class-string $hostClass
 * @param array<string, mixed> $props
 */
function compositeAttachMountedSelect(KernelBrowser $client, string $hostClass, string $actionName = 'attach', array $props = []): Select
{
    $host = compositeAttachHost($client, $hostClass, $props);
    $host->mountAction($actionName, [], ['table' => true]);

    $select = $host->getMountedActionSchema()?->getComponent('recordId');

    return $select instanceof Select ? $select : throw new LogicException("The [{$actionName}] modal carries no record select.");
}

/**
 * The keys one owner's association holds, read back cold.
 *
 * @param class-string $hostClass
 * @param array<string, mixed> $props
 *
 * @return list<string>
 */
function compositeAttachRelatedKeys(KernelBrowser $client, EntityManagerInterface $entityManager, string $hostClass, array $props = []): array
{
    $entityManager->clear();

    return array_keys(compositeAttachHost($client, $hostClass, $props)->getTable()->getRecords());
}

/**
 * Submit a key straight into the action's data, the way
 * `AttachDetachActionsTest`'s own bypass cases do, so the action body's
 * re-resolution answers rather than the select's membership validation.
 *
 * @param array<string, mixed> $props
 */
function compositeAttachOutcome(KernelBrowser $client, mixed $recordId, array $props = []): string
{
    $host = compositeAttachHost($client, ArtistShortcutsAttachTable::class, $props);
    $host->mountAction('attach', [], ['table' => true]);

    $schema = $host->getMountedActionSchema();
    $action = $host->getMountedAction() ?? throw new LogicException('Nothing mounted.');
    $action->data(['recordId' => $recordId]);

    try {
        $action->call(['schema' => $schema, 'form' => $schema]);
    } catch (LogicException $logicException) {
        return $logicException->getMessage();
    }

    return 'attached';
}

/**
 * The same bypass for the associate side, whose refusal carries a clause of its
 * own.
 *
 * @param class-string $hostClass
 */
function compositeAttachAssociateOutcome(KernelBrowser $client, string $hostClass, mixed $recordId): string
{
    $host = compositeAttachHost($client, $hostClass);
    $host->mountAction('associate', [], ['table' => true]);

    $schema = $host->getMountedActionSchema();
    $action = $host->getMountedAction() ?? throw new LogicException('Nothing mounted.');
    $action->data(['recordId' => $recordId]);

    try {
        $action->call(['schema' => $schema, 'form' => $schema]);
    } catch (LogicException $logicException) {
        return $logicException->getMessage();
    }

    return 'associated';
}

it('offers a scalar-pair target keyed by the record key, escaping included', function (): void {
    [$client] = compositeAttachSeededClient();

    expect(compositeAttachMountedSelect($client, ArtistShortcutsAttachTable::class)->getSearchResults(''))->toBe([
        'www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b' => 'Blog',
        'plain~contact' => 'Contact',
    ]);
});

it('offers the same pair from the inverse side of the many-to-many', function (): void {
    [$client] = compositeAttachSeededClient();

    expect(array_keys(compositeAttachMountedSelect($client, ArtistShortcutsAttachTable::class, props: ['inverse' => true])->getSearchResults('')))
        ->toBe(['www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b', 'plain~contact']);
});

it('offers a derived-identity target, whose first key half is an association', function (): void {
    [$client] = compositeAttachSeededClient();

    expect(compositeAttachMountedSelect($client, ArtistResidenciesAttachTable::class)->getSearchResults(''))
        ->toBe(['side-room~2026' => 'Side 2026']);
});

it('offers a single-derived-identity target, the flavour whose composite flag is false', function (): void {
    [$client] = compositeAttachSeededClient();

    expect(compositeAttachMountedSelect($client, ArtistSealsAttachTable::class)->getSearchResults(''))
        ->toBe(['L2' => 'Seal two']);
});

it('offers a target whose identifier hydrates as an object', function (): void {
    [$client] = compositeAttachSeededClient();

    // `record.handle` hydrates a `Handle`, which the hand-rolled projection used
    // to index an array with.
    expect(compositeAttachMountedSelect($client, ArtistVenuesAttachTable::class)->getSearchResults(''))
        ->toBe(['side-room' => 'Side Room']);
});

it('keys the projection arm and the hydrating arm identically', function (): void {
    [$client] = compositeAttachSeededClient();

    $projected = compositeAttachMountedSelect($client, ArtistShortcutsAttachTable::class)->getSearchResults('');
    $hydrated = compositeAttachMountedSelect($client, ArtistShortcutsAttachTable::class, props: ['titlesByClosure' => true])->getSearchResults('');

    expect(array_keys($hydrated))->toBe(array_keys($projected))
        ->and(array_values($hydrated))->toBe(['hydrated', 'hydrated']);
});

it('hydrates nothing through the projection arm', function (): void {
    [$client, $entityManager] = compositeAttachSeededClient();

    $select = compositeAttachMountedSelect($client, ArtistShortcutsAttachTable::class);
    $entityManager->clear();

    expect(array_keys($select->getSearchResults('')))->toHaveCount(2)
        ->and($entityManager->getUnitOfWork()->tryGetById(['host' => 'plain', 'path' => 'contact'], Shortcut::class))->toBeFalse();
});

it('attaches a picked composite key', function (): void {
    [$client, $entityManager] = compositeAttachSeededClient();
    $host = compositeAttachHost($client, ArtistShortcutsAttachTable::class);

    $host->mountAction('attach', [], ['table' => true]);
    $host->getMountedActionSchema()?->fill(['recordId' => 'plain~contact']);
    $host->callMountedAction();

    expect(compositeAttachRelatedKeys($client, $entityManager, ArtistShortcutsAttachTable::class))->toBe(['plain~about', 'plain~contact'])
        ->and($host->getMountedAction())->toBeNull();
});

it('attaches a derived identity and a single derived identity', function (): void {
    [$client, $entityManager] = compositeAttachSeededClient();

    foreach ([[ArtistResidenciesAttachTable::class, 'side-room~2026'], [ArtistSealsAttachTable::class, 'L2']] as [$hostClass, $key]) {
        $host = compositeAttachHost($client, $hostClass);

        $host->mountAction('attach', [], ['table' => true]);
        $host->getMountedActionSchema()?->fill(['recordId' => $key]);
        $host->callMountedAction();

        expect(compositeAttachRelatedKeys($client, $entityManager, $hostClass))->toContain($key);
    }
});

it('refuses a malformed, unknown, already-attached or duplicated key by the same widened message', function (): void {
    [$client, $entityManager] = compositeAttachSeededClient();
    $multiple = ['multiple' => true];

    expect(compositeAttachOutcome($client, 'plain'))->toContain("the key is malformed, outside recordSelectOptionsQuery()'s scope, or already attached")
        ->and(compositeAttachOutcome($client, 'nope~nope'))->toContain('its select never offered')
        ->and(compositeAttachOutcome($client, 'plain~about'))->toContain('its select never offered')
        ->and(compositeAttachOutcome($client, ['plain~contact', 'plain~contact'], $multiple))->toContain('its select never offered')
        ->and(compositeAttachOutcome($client, ['plain~contact', 'www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b'], $multiple))->toBe('attached')
        // `Artist::$shortcuts` carries `#[OrderBy(['label' => 'ASC'])]`, so the
        // reloaded collection reads in label order rather than attach order.
        ->and(compositeAttachRelatedKeys($client, $entityManager, ArtistShortcutsAttachTable::class))
        ->toBe(['plain~about', 'www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b', 'plain~contact']);
});

it('resolves two composite keys through one statement, as a balanced OR of ANDs', function (): void {
    [$client, , $recorder] = compositeAttachSeededClient();

    $recorder->reset();
    compositeAttachOutcome($client, ['plain~contact', 'www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b'], ['multiple' => true]);

    $narrowed = array_values(array_filter(
        $recorder->statements(),
        static fn (string $sql): bool => str_contains($sql, 'NOT EXISTS') && str_contains($sql, ' OR '),
    ));

    expect($narrowed)->toHaveCount(1)
        ->and($narrowed[0])->toContain('.host = ?')
        ->and($narrowed[0])->toContain('.path = ?');
});

it('refuses to associate into a one-to-many whose mapped-by is half the target identifier', function (): void {
    [$client, , $recorder] = compositeAttachSeededClient();

    $select = compositeAttachMountedSelect($client, ArtistAssociateLeasesTable::class, 'associate');
    $recorder->reset();

    expect(fn (): mixed => $select->getSearchResults(''))
        ->toThrow(LogicException::class, 'is part of the identifier of')
        ->and($recorder->statements())->toBe([]);
});

it('still associates a composite child whose mapped-by is an ordinary column', function (): void {
    [$client, $entityManager] = compositeAttachSeededClient();

    // Bob's shortcut and the two nobody curates — the `IS NULL OR <> :owner`
    // arm, keyed by the record key rather than by a single identifier field.
    expect(compositeAttachMountedSelect($client, ArtistAssociateShortcutsTable::class, 'associate')->getSearchResults(''))->toBe([
        'plain~about' => 'About',
        'www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b' => 'Blog',
        'plain~contact' => 'Contact',
    ]);

    $host = compositeAttachHost($client, ArtistAssociateShortcutsTable::class);
    $host->mountAction('associate', [], ['table' => true]);
    $host->getMountedActionSchema()?->fill(['recordId' => 'plain~about']);
    $host->callMountedAction();

    expect(compositeAttachRelatedKeys($client, $entityManager, ArtistAssociateShortcutsTable::class))->toBe(['plain~about']);
});

it('offers a converting single identifier by the key a record mints, not the one the column holds', function (): void {
    [$client] = compositeAttachSeededClient();

    // The column holds `l1`/`l2`; a projected row keyed without converting each
    // value through its field's own type would offer `l2`, which nothing the
    // submission is resolved against can then match.
    expect(compositeAttachMountedSelect($client, ArtistAssociateLockersTable::class, 'associate')->getSearchResults(''))
        ->toBe(['L2' => 'Locker two']);
});

it('refuses a malformed or unoffered key from the associate by its own widened message', function (): void {
    [$client] = compositeAttachSeededClient();

    expect(compositeAttachAssociateOutcome($client, ArtistAssociateLockersTable::class, 'l2'))
        ->toContain("the key is malformed, outside recordSelectOptionsQuery()'s scope, or already associated")
        ->and(compositeAttachAssociateOutcome($client, ArtistAssociateLockersTable::class, 'L1'))
        ->toContain('its select never offered');
});
