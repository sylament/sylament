<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Forms\Components\Select;
use Sylament\Notifications\Delivery\SessionNotifications;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Genre;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\RecordingMiddleware;
use Sylament\Tables\Tests\Fixtures\Twig\Components\SongGenresActionsTable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

use function Sylament\Tables\Tests\seededGenresClient;

uses(InteractsWithLiveComponents::class);

/*
 * The end-to-end pin for actions' attach family — `AttachAction`,
 * `DetachAction` and `DetachBulkAction`. It sits in the **tables** suite for the
 * reason `PrebuiltDeleteActionTest` and `DeleteBulkActionTest` do: only a table
 * host supplies a relationship and a selection, and the actions test kernel
 * registers neither the tables nor the forms bundle, so neither a
 * relation-scoped table nor the record select could be built there.
 *
 * Most cases run in-process on one host object, the `EditActionTest` shape: a
 * `->call()` is a fresh request that rebuilds the component and its cached
 * actions, so the mounted stack a case sets up would not be the one it then
 * calls. The success toast is a session flash, so each case pushes a request
 * carrying a session. The bulk cases are the exception — they need writable
 * selection LiveProps, so they run as real live requests with reboot disabled,
 * the in-memory SQLite living on a container service.
 */

beforeEach(function (): void {
    $request = new Request();
    $request->setSession($this::getContainer()->get('session.factory')->createSession());
    $this::getContainer()->get(RequestStack::class)->push($request);
});

/**
 * @param array<string, mixed> $props
 */
function attachHost(KernelBrowser $client, array $props = []): SongGenresActionsTable
{
    return test()->createLiveComponent(SongGenresActionsTable::class, [...['songId' => 1], ...$props], client: $client)->component();
}

function mountedRecordSelect(SongGenresActionsTable $host): Select
{
    $select = $host->getMountedActionSchema()?->getComponent('recordId');

    return $select instanceof Select ? $select : throw new LogicException('The attach modal carries no record select.');
}

/**
 * @return list<string>
 */
function genreNamesOfSong(EntityManagerInterface $entityManager, int $songId): array
{
    $entityManager->clear();

    $song = $entityManager->find(Song::class, $songId) ?? throw new LogicException('The song vanished.');

    return array_map(static fn (Genre $genre): string => $genre->getName(), $song->getGenres()->toArray());
}

/**
 * @return list<string>
 */
function drainedNotificationTitles(): array
{
    return array_map(
        static fn (array $notification): string => is_string($notification['title'] ?? null) ? $notification['title'] : '',
        test()->getContainer()->get(SessionNotifications::class)->pull(),
    );
}

it('offers only the genres the song lacks', function (): void {
    [$client] = seededGenresClient();
    $host = attachHost($client);

    $host->mountAction('attach', [], ['table' => true]);

    $select = mountedRecordSelect($host);

    expect($select->getSearchResults(''))->toBe([4 => 'Metal'])
        ->and($select->getSearchResults('met'))->toBe([4 => 'Metal'])
        ->and($select->getSearchResults('rock'))->toBe([]);
});

it("titles the options through the table's record-title closure when it has one", function (): void {
    [$client] = seededGenresClient();
    $host = attachHost($client, ['titlesByClosure' => true]);

    $host->mountAction('attach', [], ['table' => true]);

    expect(mountedRecordSelect($host)->getSearchResults(''))->toBe([4 => 'METAL']);
});

it('attaches the picked genre, toasts and unmounts', function (): void {
    [$client, $entityManager] = seededGenresClient();
    $host = attachHost($client);

    $host->mountAction('attach', [], ['table' => true]);
    $host->getMountedActionSchema()?->fill(['recordId' => 4]);
    $host->callMountedAction();

    expect(genreNamesOfSong($entityManager, 1))->toBe(['Rock', 'Pop', 'Jazz', 'Metal'])
        ->and($host->getMountedAction())->toBeNull()
        ->and(drainedNotificationTitles())->toBe(['Attached']);
});

it('stays mounted with an empty select on the attach-another arm, and still toasts', function (): void {
    [$client, $entityManager] = seededGenresClient();
    $host = attachHost($client);

    $host->mountAction('attach', [], ['table' => true]);
    $host->getMountedActionSchema()?->fill(['recordId' => 4]);
    $host->callMountedAction(['another' => true]);

    expect(genreNamesOfSong($entityManager, 1))->toContain('Metal')
        ->and($host->getMountedActions())->toHaveCount(1)
        ->and($host->getMountedActionData(0))->toBe(['recordId' => null])
        ->and(drainedNotificationTitles())->toBe(['Attached']);
});

it('attaches two genres in one call when the select is multiple', function (): void {
    [$client, $entityManager] = seededGenresClient();
    $host = attachHost($client, ['songId' => 2, 'multiple' => true]);

    $host->mountAction('attach', [], ['table' => true]);
    $host->getMountedActionSchema()?->fill(['recordId' => [1, 2]]);
    $host->callMountedAction();

    // Sorted: nothing orders the join table, so the reloaded collection's row
    // order is the driver's, not a contract.
    $names = genreNamesOfSong($entityManager, 2);
    sort($names);

    expect($names)->toBe(['Jazz', 'Metal', 'Pop', 'Rock']);
});

/*
 * The smuggled key meets two gates, and both are asserted: the select's own
 * label probe fails validation first — an already-attached key resolves no
 * label through the candidate query — and behind it the submit re-resolves the
 * key through that same query, which is what refuses a key reaching the action
 * body with the schema bypassed.
 */

it('fails validation for a key the candidate query never offered, changing nothing', function (): void {
    [$client, $entityManager] = seededGenresClient();
    $host = attachHost($client);

    $host->mountAction('attach', [], ['table' => true]);
    $host->getMountedActionSchema()?->fill(['recordId' => 1]);

    expect(fn (): mixed => $host->callMountedAction())->toThrow(ValidationException::class)
        ->and(genreNamesOfSong($entityManager, 1))->toBe(['Rock', 'Pop', 'Jazz']);
});

it('refuses the same key in the action body when validation is bypassed', function (): void {
    [$client, $entityManager] = seededGenresClient();
    $host = attachHost($client);

    $host->mountAction('attach', [], ['table' => true]);

    $schema = $host->getMountedActionSchema();
    $action = $host->getMountedAction() ?? throw new LogicException('Nothing mounted.');
    $action->data(['recordId' => 1]);

    expect(fn (): mixed => $action->call(['schema' => $schema, 'form' => $schema]))
        ->toThrow(LogicException::class, 'its select never offered')
        ->and(genreNamesOfSong($entityManager, 1))->toBe(['Rock', 'Pop', 'Jazz']);
});

it('answers the candidates without a search when the select is preloaded', function (): void {
    [$client] = seededGenresClient();
    $host = attachHost($client, ['preloads' => true]);

    $host->mountAction('attach', [], ['table' => true]);

    expect(mountedRecordSelect($host)->getOptions())->toBe([4 => 'Metal']);
});

it('detaches one row, leaving the rest, and titles the modal by the record', function (): void {
    [$client, $entityManager] = seededGenresClient();
    $host = attachHost($client);

    $host->mountAction('detach', [], ['table' => true, 'recordKey' => '1']);

    expect($host->getMountedAction()?->getModalHeading())->toBe('Detach Rock');

    $host->callMountedAction();

    expect(genreNamesOfSong($entityManager, 1))->toBe(['Pop', 'Jazz'])
        ->and($host->getMountedAction())->toBeNull()
        ->and(drainedNotificationTitles())->toBe(['Detached']);
});

it('detaches the whole selection and clears it', function (): void {
    [$client, $entityManager] = seededGenresClient();

    $host = $this->createLiveComponent(SongGenresActionsTable::class, ['songId' => 1], client: $client)
        ->set('selectedTableRecords', ['1', '2'])
        ->call('mountAction', ['name' => 'detach', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction')
        ->component();

    expect(genreNamesOfSong($entityManager, 1))->toBe(['Jazz'])
        ->and($host->selectedTableRecordKeys)->toBe([])
        ->and($host->getMountedAction()?->getStatus())->toBeNull();
});

it('detaches by key without ever selecting the rows', function (): void {
    [$client, $entityManager, $recorder] = seededGenresClient();

    $byKey = detachStatements($client, $recorder, fetchesRecords: false);

    expect(genreNamesOfSong($entityManager, 1))->toBe(['Jazz'])
        ->and(hydratingKeySelections($byKey))->toBe([]);
});

it('selects the rows on the fetching arm, which is the contrast', function (): void {
    [$client, $entityManager, $recorder] = seededGenresClient();

    $fetched = detachStatements($client, $recorder, fetchesRecords: true);

    expect(genreNamesOfSong($entityManager, 1))->toBe(['Jazz'])
        ->and(hydratingKeySelections($fetched))->toHaveCount(1);
});

/**
 * The statements that both read a genre's columns and narrow by the selected
 * keys — a hydrating selection. The by-key arm plucks the same keys, but its
 * projection is the identifier alone, so only the fetching arm has one.
 *
 * @param list<string> $statements
 *
 * @return list<string>
 */
function hydratingKeySelections(array $statements): array
{
    return array_values(array_filter(
        $statements,
        static fn (string $sql): bool => (bool) preg_match('/name_\d+.+IN \(\?/', $sql),
    ));
}

/**
 * The confirmed bulk detach's *second* request alone: the recorder is reset
 * between the mount and the call, so the statement list starts where the action
 * body does.
 *
 * @return list<string>
 */
function detachStatements(KernelBrowser $client, RecordingMiddleware $recorder, bool $fetchesRecords): array
{
    $component = test()->createLiveComponent(SongGenresActionsTable::class, ['songId' => 1, 'fetchesRecords' => $fetchesRecords], client: $client)
        ->set('selectedTableRecords', ['1', '2'])
        ->call('mountAction', ['name' => 'detach', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]]);

    $recorder->reset();

    $component->call('callMountedAction');

    return $recorder->statements();
}
