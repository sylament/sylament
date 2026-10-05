<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use RuntimeException;
use Sylament\Support\Tests\Doctrine\FailingWrite;
use Sylament\Support\Tests\Doctrine\RecordingConnection;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Sylament\Tables\Tests\Fixtures\Actions\RecordingDeleteBulkAction;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The two `report-*` actions open no modal, so `mountAction` calls them in the
 * same request and the toast is in *that* response — a second
 * `callMountedAction` would find nothing mounted and render a page the flash
 * bag has already been drained for.
 *
 * The end-to-end pin for actions' `DeleteBulkAction`, sitting here for the
 * reason `PrebuiltDeleteActionTest` does: only a table host supplies a
 * selection, and the actions test kernel has neither a table nor a database.
 * Kernel reboot is disabled throughout — the fixture database is an in-memory
 * SQLite held by a container service, so a reboot between the mount and the
 * confirmed call would hand the second request an empty one.
 *
 * The transaction assertions read the `Recording*` middleware's statement list,
 * where {@see RecordingConnection} records BEGIN / COMMIT / ROLLBACK markers
 * beside the SQL. Each of those cases is written twice — with the flag and
 * without — because the ORM opens a transaction around every flush of its own,
 * so only the *contrast* proves the action's wrap did anything. That is also why
 * the committing case asserts the BEGIN lands **before the selection SELECT**
 * rather than merely around the DELETE: the flush's own transaction already
 * brackets the DELETE, so bracketing proves nothing, while only the action's
 * wrap can open a transaction before the records are even resolved.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface, RecordingMiddleware}
 */
function seededBulkSongsClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $recorder = test()->getContainer()->get(RecordingMiddleware::class);

    EntityManagerFactory::seed(
        $entityManager,
        new Song(1, 'Airbag', 100),
        new Song(2, 'Paranoid Android', 200),
        new Song(3, 'Exit Music', 200),
        new Song(4, 'Let Down', 100),
    );

    $recorder->reset();

    return [$client, $entityManager, $recorder];
}

/**
 * The by-key delete's failing rig: `Song.artist_id` references `Artist` with no
 * cascade, so one seeded song is enough to make the DQL `DELETE` of its artist
 * violate a foreign key, which every platform the suites run on enforces.
 *
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function seededBulkArtistsClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $radiohead = new Artist(1, 'Radiohead');

    EntityManagerFactory::seed($entityManager, $radiohead, new Song(1, 'Airbag', 100, $radiohead));

    return [$client, $entityManager];
}

/**
 * @return list<int>
 */
function survivingSongIds(EntityManagerInterface $entityManager): array
{
    $entityManager->clear();

    /** @var list<Song> $songs */
    $songs = $entityManager->getRepository(Song::class)->findBy([], ['id' => 'ASC']);

    return array_map(static fn (Song $song): int => $song->getId(), $songs);
}

/**
 * The confirmed bulk delete's *second* request alone: the recorder is reset
 * between the mount and the call, so the statement list starts where the action
 * body does and a position assertion means something.
 *
 * @return list<string>
 */
function confirmedBulkDeleteStatements(KernelBrowser $client, RecordingMiddleware $recorder, bool $usesTransaction): array
{
    $component = test()->createLiveComponent('BulkDeleteSongsTable', ['usesTransaction' => $usesTransaction], client: $client)
        ->set('selectedTableRecords', ['2'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]]);

    $recorder->reset();

    $component->call('callMountedAction');

    return $recorder->statements();
}

/**
 * The index of the first recorded statement starting with $prefix, or a
 * sentinel past every index so a "precedes" assertion fails on an absent one.
 *
 * @param list<string> $statements
 */
function firstStatementIndex(array $statements, string $prefix): int
{
    foreach ($statements as $index => $sql) {
        if (str_starts_with($sql, $prefix)) {
            return $index;
        }
    }

    return PHP_INT_MAX;
}

it('deletes exactly the selected records', function (): void {
    [$client, $entityManager] = seededBulkSongsClient();

    $this->createLiveComponent('BulkDeleteSongsTable', client: $client)
        ->set('selectedTableRecords', ['2', '4'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    expect(survivingSongIds($entityManager))->toBe([1, 3]);
});

it('deletes everything but the deselected records while tracking', function (): void {
    [$client, $entityManager] = seededBulkSongsClient();

    $this->createLiveComponent('BulkDeleteSongsTable', client: $client)
        ->set('isTrackingDeselectedTableRecords', true)
        ->set('deselectedTableRecords', ['1', '3'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    expect(survivingSongIds($entityManager))->toBe([1, 3]);
});

it('leaves a selected record the active search filters out', function (): void {
    [$client, $entityManager] = seededBulkSongsClient();

    $this->createLiveComponent('BulkDeleteSongsTable', client: $client)
        ->set('tableSearch', 'Let Down')
        ->set('selectedTableRecords', ['2', '4'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    expect(survivingSongIds($entityManager))->toBe([1, 2, 3]);
});

it('leaves a selected record the selectability callback refuses', function (): void {
    [$client, $entityManager] = seededBulkSongsClient();

    $this->createLiveComponent('BulkDeleteSongsTable', ['checksSelectable' => true], client: $client)
        ->set('selectedTableRecords', ['2', '3'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    expect(survivingSongIds($entityManager))->toBe([1, 3, 4]);
});

it('clears the selection props after the delete', function (): void {
    [$client] = seededBulkSongsClient();

    $host = $this->createLiveComponent('BulkDeleteSongsTable', client: $client)
        ->set('selectedTableRecords', ['2', '4'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction')
        ->component();

    expect($host->selectedTableRecordKeys)->toBe([])
        ->and($host->deselectedTableRecords)->toBe([])
        ->and($host->isTrackingDeselectedTableRecords)->toBeFalse();
});

it('deletes by key in one statement without fetching the records', function (): void {
    [$client, $entityManager, $recorder] = seededBulkSongsClient();

    $this->createLiveComponent('BulkDeleteSongsTable', ['fetchesRecords' => false], client: $client)
        ->set('selectedTableRecords', ['2', '4'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    $deletes = array_values(array_filter(
        $recorder->statements(),
        static fn (string $sql): bool => str_starts_with($sql, 'DELETE'),
    ));

    expect(survivingSongIds($entityManager))->toBe([1, 3])
        ->and($deletes)->toHaveCount(1);
});

it('reports the affected row count of the by-key delete as the successful count', function (): void {
    [$client] = seededBulkSongsClient();

    $html = (string) $this->createLiveComponent('BulkDeleteSongsTable', ['fetchesRecords' => false], client: $client)
        ->set('selectedTableRecords', ['2', '4'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction')
        ->render();

    // The status is the count comparison: `getSelectedRecords()` minted the total
    // from the two plucked keys, so only a matching affected-row count leaves the
    // action successful rather than reporting a partial delete.
    expect($html)->toContain('Deleted')
        ->and($html)->toContain('sy-status-success')
        ->and($html)->not->toContain('Failed to delete');
});

it('answers a failed by-key delete with the danger toast, leaving the rows', function (): void {
    [$client, $entityManager] = seededBulkArtistsClient();

    $html = (string) $this->createLiveComponent('BulkDeleteArtistsTable', client: $client)
        ->set('selectedTableRecords', ['1'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction')
        ->render();

    $entityManager->clear();

    expect($html)->toContain('Failed to delete 1 records')
        ->and($html)->toContain('sy-status-danger')
        ->and($entityManager->getRepository(Artist::class)->findAll())->toHaveCount(1);
});

it('mints the counters off the selection and warns on a partial failure', function (): void {
    [$client] = seededBulkSongsClient();

    $html = (string) $this->createLiveComponent('BulkDeleteSongsTable', client: $client)
        ->set('selectedTableRecords', ['2', '3', '4'])
        ->call('mountAction', ['name' => 'report-one', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->render();

    expect($html)->toContain('Archived 2 of 3')
        ->and($html)->toContain('sy-status-warning');
});

it('resolves the selection query by parameter name and by QueryBuilder type alike', function (): void {
    [$client] = seededBulkSongsClient();

    $html = (string) $this->createLiveComponent('BulkDeleteSongsTable', client: $client)
        ->set('selectedTableRecords', ['2', '4'])
        ->call('mountAction', ['name' => 'inspect-query', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->render();

    // The fixture answers -1 when the two injections disagree, so a matching
    // count is what says both arms resolved the same constrained query.
    expect($html)->toContain('Both queries answered 2');
});

it('reports every record failed with a danger toast on a complete failure', function (): void {
    [$client] = seededBulkSongsClient();

    $html = (string) $this->createLiveComponent('BulkDeleteSongsTable', client: $client)
        ->set('selectedTableRecords', ['2', '3', '4'])
        ->call('mountAction', ['name' => 'report-all', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->render();

    expect($html)->toContain('Failed to archive 3 records')
        ->and($html)->toContain('sy-status-danger');
});

it('wraps the delete in a transaction it commits', function (): void {
    [$client, $entityManager, $recorder] = seededBulkSongsClient();

    $statements = confirmedBulkDeleteStatements($client, $recorder, usesTransaction: true);
    $delete = firstStatementIndex($statements, 'DELETE');

    expect(survivingSongIds($entityManager))->toBe([1, 3, 4])
        ->and($statements)->not->toContain(RecordingConnection::ROLLBACK)
        ->and(firstStatementIndex($statements, RecordingConnection::BEGIN))
        ->toBeLessThan(firstStatementIndex($statements, 'SELECT'))
        ->and(array_key_last(array_filter($statements, static fn (string $sql): bool => $sql === RecordingConnection::COMMIT)))->toBeGreaterThan($delete);
});

it('opens no transaction of its own when the same delete runs unwrapped', function (): void {
    [$client, $entityManager, $recorder] = seededBulkSongsClient();

    $statements = confirmedBulkDeleteStatements($client, $recorder, usesTransaction: false);

    expect(survivingSongIds($entityManager))->toBe([1, 3, 4])
        ->and(firstStatementIndex($statements, 'SELECT'))
        ->toBeLessThan(firstStatementIndex($statements, RecordingConnection::BEGIN));
});

it('rolls the whole batch back when the bulk body throws', function (): void {
    [$client, $entityManager, $recorder] = seededBulkSongsClient();

    $component = $this->createLiveComponent('BulkDeleteSongsTable', ['usesTransaction' => true], client: $client)
        ->set('selectedTableRecords', ['2']);

    expect(fn (): object => $component->call('mountAction', ['name' => 'boom', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]]))
        ->toThrow(RuntimeException::class, 'The bulk body blew up.')
        ->and($recorder->statements())->toContain(RecordingConnection::ROLLBACK)
        ->and(survivingSongIds($entityManager))->toBe([1, 2, 3, 4]);
});

it('keeps the removal when the same throwing body runs outside a transaction', function (): void {
    [$client, $entityManager, $recorder] = seededBulkSongsClient();

    $component = $this->createLiveComponent('BulkDeleteSongsTable', client: $client)
        ->set('selectedTableRecords', ['2']);

    expect(fn (): object => $component->call('mountAction', ['name' => 'boom', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]]))
        ->toThrow(RuntimeException::class, 'The bulk body blew up.')
        ->and($recorder->statements())->not->toContain(RecordingConnection::ROLLBACK)
        ->and(survivingSongIds($entityManager))->toBe([1, 3, 4]);
});

it('rolls back when the bulk body cancels with the rollback flag', function (): void {
    [$client, $entityManager, $recorder] = seededBulkSongsClient();

    $this->createLiveComponent('BulkDeleteSongsTable', ['usesTransaction' => true], client: $client)
        ->set('selectedTableRecords', ['2'])
        ->call('mountAction', ['name' => 'give-up', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]]);

    expect($recorder->statements())->toContain(RecordingConnection::ROLLBACK)
        ->and(survivingSongIds($entityManager))->toBe([1, 2, 3, 4]);
});

it('keeps the removal when the same cancelling body runs outside a transaction', function (): void {
    [$client, $entityManager, $recorder] = seededBulkSongsClient();

    $this->createLiveComponent('BulkDeleteSongsTable', client: $client)
        ->set('selectedTableRecords', ['2'])
        ->call('mountAction', ['name' => 'give-up', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]]);

    expect($recorder->statements())->not->toContain(RecordingConnection::ROLLBACK)
        ->and(survivingSongIds($entityManager))->toBe([1, 3, 4]);
});

it('leaves the unselectable record behind on the by-key delete too', function (): void {
    [$client, $entityManager] = seededBulkSongsClient();

    $this->createLiveComponent('BulkDeleteSongsTable', ['checksSelectable' => true, 'fetchesRecords' => false], client: $client)
        ->set('selectedTableRecords', ['2', '3'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    expect(survivingSongIds($entityManager))->toBe([1, 3, 4]);
});

/*
 * A developer error — a by-key delete on a lane that has no query — must
 * propagate rather than become the danger toast. `deleteSelectedRecordsByKey()`
 * resolves the table, its entity manager and its record class *inside* the
 * action's own `catch (Throwable)`, which alone would make it indistinguishable
 * from a failed statement, so the action resolves them once before the try and
 * the override point is never reached. `DissociateBulkAction` places its mapping
 * refusals before the try the same way.
 */
it('propagates a by-key delete refusal instead of answering with the danger toast', function (): void {
    RecordingDeleteBulkAction::$handedKeys = [];
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $component = $this->createLiveComponent('RecordsLaneDeleteTable', client: $client)
        ->set('selectedTableRecords', ['1'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]]);

    expect(fn (): object => $component->call('callMountedAction'))
        ->toThrow(LogicException::class, 'deletes selected records by key, which needs a [query()] table')
        ->and(RecordingDeleteBulkAction::$handedKeys)->toBe([]);
});

/*
 * The sibling refusal, one line above it: the same action mounted outside the table
 * context resolves through the host's own action method, so nothing hands it a table
 * even though the host is a table host and answers the selection. It is the arm that
 * says the table lookup itself sits outside the `catch (Throwable)`.
 */
it('propagates a by-key delete mounted outside the table context', function (): void {
    RecordingDeleteBulkAction::$handedKeys = [];
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $component = $this->createLiveComponent('RecordsLaneDeleteTable', client: $client)
        ->set('selectedTableRecords', ['1'])
        ->call('mountAction', ['name' => 'untabledDelete', 'arguments' => [], 'context' => ['bulk' => true]]);

    expect(fn (): object => $component->call('callMountedAction'))
        ->toThrow(LogicException::class, 'deletes selected records by key, which only a table can supply')
        ->and(RecordingDeleteBulkAction::$handedKeys)->toBe([]);
});

/**
 * @return array{KernelBrowser, EntityManagerInterface, RecordingMiddleware}
 */
function seededBulkShortcutsClient(Shortcut ...$shortcuts): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $recorder = test()->getContainer()->get(RecordingMiddleware::class);

    EntityManagerFactory::seed($entityManager, ...$shortcuts);

    $recorder->reset();

    return [$client, $entityManager, $recorder];
}

function bulkShortcut(string $host, string $path, string $label, int $position): Shortcut
{
    $shortcut = new Shortcut();
    $shortcut->setHost($host);
    $shortcut->setPath($path);
    $shortcut->setLabel($label);
    $shortcut->setPosition($position);
    $shortcut->setActive(true);

    return $shortcut;
}

it('deletes a small composite selection in one statement and opens no transaction', function (): void {
    [$client, $entityManager, $recorder] = seededBulkShortcutsClient(
        bulkShortcut('sylament.test', '/docs/a b', 'Docs', 1),
        bulkShortcut('example.org', 'home', 'Home', 2),
        bulkShortcut('example.org', 'about', 'About', 3),
    );

    $this->createLiveComponent('CompositeShortcutsTable', client: $client)
        ->set('selectedTableRecords', ['sylament=2Etest~=2Fdocs=2Fa=20b', 'example=2Eorg~about'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    $statements = $recorder->statements();
    $deletes = array_values(array_filter($statements, static fn (string $sql): bool => str_starts_with($sql, 'DELETE')));

    $entityManager->clear();

    expect($deletes)->toHaveCount(1)
        ->and($statements)->not->toContain(RecordingConnection::BEGIN)
        ->and($entityManager->getRepository(Shortcut::class)->findAll())->toHaveCount(1);
});

/*
 * The chunk boundary, paid for at its real price. `IdentifierConstraint::chunk()`
 * splits a two-field key list at `min(500, intdiv(2000, 2))` = 500, so 501 rows
 * is the cheapest selection that is more than one statement, and 501 rows cost
 * 5 ms to seed through the ORM (measured). The client sends no key list at all:
 * the tracking strategy with an empty deselection *is* select-all, so the 501
 * keys are plucked server-side, which is also the shape a real select-all takes.
 * Nothing here reaches for a seam the production code would not otherwise have.
 */
it('runs a chunked composite delete as one transaction and sums the affected rows', function (): void {
    $shortcuts = [];

    for ($index = 1; $index <= 501; $index++) {
        $shortcuts[] = bulkShortcut('bulk.test', '/p/' . $index, 'Bulk ' . $index, $index);
    }

    [$client, $entityManager, $recorder] = seededBulkShortcutsClient(...$shortcuts);

    $html = (string) $this->createLiveComponent('CompositeShortcutsTable', client: $client)
        ->set('isTrackingDeselectedTableRecords', true)
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction')
        ->render();

    $statements = $recorder->statements();
    $deletes = array_values(array_filter($statements, static fn (string $sql): bool => str_starts_with($sql, 'DELETE')));

    $entityManager->clear();

    // One BEGIN…COMMIT around both statements is the whole point: the ORM opens
    // none of its own here, because a DQL delete never flushes, so every marker
    // in the list is the action's.
    expect($deletes)->toHaveCount(2)
        ->and(array_count_values($statements)[RecordingConnection::BEGIN] ?? 0)->toBe(1)
        ->and(array_count_values($statements)[RecordingConnection::COMMIT] ?? 0)->toBe(1)
        ->and($statements)->not->toContain(RecordingConnection::ROLLBACK)
        ->and(firstStatementIndex($statements, RecordingConnection::BEGIN))->toBeLessThan(firstStatementIndex($statements, 'DELETE'))
        ->and(array_key_last(array_filter($statements, static fn (string $sql): bool => $sql === RecordingConnection::COMMIT)))->toBeGreaterThan(array_key_last(array_filter($statements, static fn (string $sql): bool => str_starts_with($sql, 'DELETE'))))
        ->and($entityManager->getRepository(Shortcut::class)->findAll())->toBe([])
        ->and($html)->toContain('Deleted')
        ->and($html)->toContain('sy-status-success')
        ->and($html)->not->toContain('Failed to delete');
});

/*
 * The rollback the transaction exists for, which the committing case above cannot
 * show. The lever is a FailingWrite trigger the test creates after seeding —
 * `Shortcut` carries no foreign key another row could violate, and the recorder is a
 * pass-through — so no fixture moves. The failing row has to be in chunk **two**.
 * FailingWrite fails the 501st row deleted, so the row the first chunk leaves over
 * (whichever it is) fails in chunk two, whatever order the pluck returns the keys in.
 * The DELETE count is asserted first because it is the only thing that separates
 * "chunk two failed and chunk one was rolled back" from "chunk one failed and nothing
 * was ever deleted" — the recorder records a statement before it runs, so a failing
 * chunk still counts.
 */
it('rolls a chunked composite delete back when a later chunk fails', function (): void {
    $shortcuts = [];

    for ($index = 1; $index <= 501; $index++) {
        $shortcuts[] = bulkShortcut('bulk.test', '/p/' . $index, 'Bulk ' . $index, $index);
    }

    [$client, $entityManager, $recorder] = seededBulkShortcutsClient(...$shortcuts);

    FailingWrite::afterDeletes($entityManager, Shortcut::class, 500);

    $recorder->reset();

    $html = (string) $this->createLiveComponent('CompositeShortcutsTable', client: $client)
        ->set('isTrackingDeselectedTableRecords', true)
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction')
        ->render();

    $statements = $recorder->statements();
    $deletes = array_values(array_filter($statements, static fn (string $sql): bool => str_starts_with($sql, 'DELETE')));

    $entityManager->clear();

    expect($deletes)->toHaveCount(2)
        ->and($statements)->toContain(RecordingConnection::ROLLBACK)
        ->and($statements)->not->toContain(RecordingConnection::COMMIT)
        ->and($entityManager->getRepository(Shortcut::class)->findAll())->toHaveCount(501)
        ->and($html)->toContain('Failed to delete 501 records')
        ->and($html)->toContain('sy-status-danger');
});

/*
 * The by-key arm's override point, from the subclass an application writes:
 * `deleteSelectedRecordsByKey()` is handed the plucked keys once the action has
 * resolved everything it can refuse, what it answers is the successful count
 * the toast reads, and it runs inside the action's `catch (Throwable)`. The
 * plucked keys are compared as a set: the selection is ordered by the
 * identifier tiebreak, but the override's contract is the keys, not their order.
 */
it('hands an override of deleteSelectedRecordsByKey() the plucked keys and reports what it answers', function (): void {
    RecordingDeleteBulkAction::$handedKeys = [];
    [$client, $entityManager] = seededBulkSongsClient();

    $html = (string) $this->createLiveComponent('BulkDeleteSongsTable', ['fetchesRecords' => false, 'deleteOverride' => RecordingDeleteBulkAction::DELEGATES], client: $client)
        ->set('selectedTableRecords', ['2', '4'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction')
        ->render();

    expect(RecordingDeleteBulkAction::$handedKeys)->toHaveCount(1)
        ->and(RecordingDeleteBulkAction::$handedKeys[0])->toEqualCanonicalizing(['2', '4'])
        ->and(survivingSongIds($entityManager))->toBe([1, 3])
        ->and($html)->toContain('Deleted')
        ->and($html)->toContain('sy-status-success')
        ->and($html)->not->toContain('Failed to delete');
});

/*
 * An empty selection reports a count of 0 before the override point is
 * reached, so an override is never handed an empty key list.
 */
it('reports an empty by-key selection as nothing deleted without calling the override', function (): void {
    RecordingDeleteBulkAction::$handedKeys = [];
    [$client, $entityManager, $recorder] = seededBulkSongsClient();

    $html = (string) $this->createLiveComponent('BulkDeleteSongsTable', ['fetchesRecords' => false, 'deleteOverride' => RecordingDeleteBulkAction::DELEGATES], client: $client)
        ->set('selectedTableRecords', [])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction')
        ->render();

    $deletes = array_values(array_filter($recorder->statements(), static fn (string $sql): bool => str_starts_with($sql, 'DELETE')));

    expect(RecordingDeleteBulkAction::$handedKeys)->toBe([])
        ->and($deletes)->toBe([])
        ->and(survivingSongIds($entityManager))->toBe([1, 2, 3, 4])
        ->and($html)->toContain('Deleted')
        ->and($html)->toContain('sy-status-success')
        ->and($html)->not->toContain('Failed to delete')
        ->and($html)->not->toContain('sy-status-warning');
});

it('reports an override that answers fewer rows than it was handed as a partial delete', function (): void {
    RecordingDeleteBulkAction::$handedKeys = [];
    [$client, $entityManager] = seededBulkSongsClient();

    $html = (string) $this->createLiveComponent('BulkDeleteSongsTable', ['fetchesRecords' => false, 'deleteOverride' => RecordingDeleteBulkAction::UNDERCOUNTS], client: $client)
        ->set('selectedTableRecords', ['2', '4'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction')
        ->render();

    expect(RecordingDeleteBulkAction::$handedKeys)->toHaveCount(1)
        ->and($html)->toContain('Deleted 1 of 2')
        ->and($html)->toContain('sy-status-warning')
        ->and(survivingSongIds($entityManager))->toBe([1, 2, 3, 4]);
});

it('reports an override that throws as a failed delete', function (): void {
    RecordingDeleteBulkAction::$handedKeys = [];
    [$client, $entityManager] = seededBulkSongsClient();

    $html = (string) $this->createLiveComponent('BulkDeleteSongsTable', ['fetchesRecords' => false, 'deleteOverride' => RecordingDeleteBulkAction::REFUSES], client: $client)
        ->set('selectedTableRecords', ['2', '4'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction')
        ->render();

    expect(RecordingDeleteBulkAction::$handedKeys)->toHaveCount(1)
        ->and($html)->toContain('Failed to delete 2 records')
        ->and($html)->toContain('sy-status-danger')
        ->and(survivingSongIds($entityManager))->toBe([1, 2, 3, 4]);
});

/*
 * The chunking stays the parent's: the 501-row rig above is two statements in
 * one transaction, and the override sees the selection whole, once.
 */
it('hands an override a two-chunk selection in one call, which its parent deletes in one transaction', function (): void {
    RecordingDeleteBulkAction::$handedKeys = [];
    $shortcuts = [];

    for ($index = 1; $index <= 501; $index++) {
        $shortcuts[] = bulkShortcut('bulk.test', '/p/' . $index, 'Bulk ' . $index, $index);
    }

    [$client, $entityManager, $recorder] = seededBulkShortcutsClient(...$shortcuts);

    $html = (string) $this->createLiveComponent('CompositeShortcutsTable', ['deleteOverride' => RecordingDeleteBulkAction::DELEGATES], client: $client)
        ->set('isTrackingDeselectedTableRecords', true)
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction')
        ->render();

    $statements = $recorder->statements();
    $deletes = array_values(array_filter($statements, static fn (string $sql): bool => str_starts_with($sql, 'DELETE')));

    $entityManager->clear();

    expect(RecordingDeleteBulkAction::$handedKeys)->toHaveCount(1)
        ->and(RecordingDeleteBulkAction::$handedKeys[0])->toHaveCount(501)
        ->and(array_unique(RecordingDeleteBulkAction::$handedKeys[0]))->toHaveCount(501)
        ->and($deletes)->toHaveCount(2)
        ->and(array_count_values($statements)[RecordingConnection::BEGIN] ?? 0)->toBe(1)
        ->and(array_count_values($statements)[RecordingConnection::COMMIT] ?? 0)->toBe(1)
        ->and($entityManager->getRepository(Shortcut::class)->findAll())->toBe([])
        ->and($html)->toContain('sy-status-success');
});
