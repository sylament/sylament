<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Membership;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Note;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Actions\Tests\Fixtures\Twig\Components\ActionSchemaField;
use Sylament\Actions\Tests\Fixtures\Twig\Components\CupboardKeyHost;
use Sylament\Schemas\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * A replicate whose modal opens on the original's key, over the three shapes of
 * assigned key the modal can leave unchanged, and a key field locked with
 * `disabledOn('edit')`, which only an action named `edit` honours. In-process
 * on one host object, for the reason `ReplicateActionTest` states; each case
 * pushes a request carrying a session for the success toast.
 */

beforeEach(function (): void {
    $request = new Request();
    $request->setSession($this::getContainer()->get('session.factory')->createSession());

    $this::getContainer()->get(RequestStack::class)->push($request);
});

/**
 * @param class-string $class
 */
function replicateTakenKeyOriginal(string $class): void
{
    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    if ($class === Membership::class) {
        $note = new Note();
        $note->setTitle('owner');
        $entityManager->persist($note);

        $membership = new Membership();
        $membership->setNote($note);
        $membership->setRole('editor');
        $membership->setLabel('Editors');
        $entityManager->persist($membership);
    }

    if ($class === Redirect::class) {
        $redirect = new Redirect();
        $redirect->setHost('example.com');
        $redirect->setPath('/old');
        $redirect->setTarget('/new');
        $entityManager->persist($redirect);
    }

    if ($class === Ticket::class) {
        $ticket = new Ticket();
        $ticket->setCode('T-1');
        $ticket->setTitle('Alpha');
        $entityManager->persist($ticket);
    }

    $entityManager->flush();
}

/**
 * @param class-string $class
 */
function replicateTakenKeyMounted(string $class, string $recordKey, string $action): CupboardKeyHost
{
    $host = test()->createLiveComponent(CupboardKeyHost::class, ['recordClass' => $class])->component();
    assert($host instanceof CupboardKeyHost);

    $host->mountAction($action, [], ['recordKey' => $recordKey]);

    return $host;
}

/**
 * @return array<string, list<string>>
 */
function replicateTakenKeyRefusal(CupboardKeyHost $host): array
{
    try {
        $host->callMountedAction();
    } catch (ValidationException $validationException) {
        return $validationException->getErrors();
    }

    test()->fail('Expected the replicate to be refused with a ValidationException.');
}

/**
 * @return list<array<string, mixed>>
 */
function replicateTakenKeyRows(string $sql): array
{
    return test()->getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchAllAssociative($sql);
}

function replicateTakenKeyField(CupboardKeyHost $host, string $name): ActionSchemaField
{
    foreach ($host->getMountedActionSchema()?->getFlatComponents() ?? [] as $component) {
        if ($component instanceof ActionSchemaField && $component->getStatePath(false) === $name) {
            return $component;
        }
    }

    test()->fail(sprintf('No field [%s] in the mounted schema.', $name));
}

it('answers a modal left on the original key with the taken-key message under every scalar key field it maps', function (string $class, string $recordKey, array $fill, array $paths, string $rows, array $stored): void {
    replicateTakenKeyOriginal($class);

    $host = replicateTakenKeyMounted($class, $recordKey, 'replicate');

    expect($host->getMountedActionData(0))->toBe($fill)
        ->and(replicateTakenKeyRefusal($host))->toBe(array_fill_keys($paths, ['This value is already used.']))
        ->and($this::getContainer()->get(EntityManagerInterface::class)->isOpen())->toBeTrue()
        ->and($host->mountedActions)->toHaveCount(1)
        ->and(replicateTakenKeyRows($rows))->toBe($stored);
})->with([
    'a scalar pair' => [Redirect::class, 'example=2Ecom~=2Fold', ['host' => 'example.com', 'path' => '/old', 'target' => '/new'], ['mountedActionsData.0.host', 'mountedActionsData.0.path'], 'SELECT host, path, target FROM Redirect', [['host' => 'example.com', 'path' => '/old', 'target' => '/new']]],
    'an association beside a scalar' => [Membership::class, '1~editor', ['role' => 'editor', 'label' => 'Editors'], ['mountedActionsData.0.role'], 'SELECT note_id, role, label FROM Membership', [['note_id' => 1, 'role' => 'editor', 'label' => 'Editors']]],
    'a single string key' => [Ticket::class, 'T-1', ['code' => 'T-1', 'title' => 'Alpha'], ['mountedActionsData.0.code'], 'SELECT code, title FROM Ticket', [['code' => 'T-1', 'title' => 'Alpha']]],
]);

it('writes the replica once the modal key is changed', function (string $class, string $recordKey, string $field, string $value, string $rows, array $stored): void {
    replicateTakenKeyOriginal($class);

    $host = replicateTakenKeyMounted($class, $recordKey, 'replicate');
    $host->mountedActionsData[0][$field] = $value;
    $host->callMountedAction();

    expect($host->mountedActions)->toBe([])
        ->and(replicateTakenKeyRows($rows))->toBe($stored);
})->with([
    'a scalar pair' => [Redirect::class, 'example=2Ecom~=2Fold', 'path', '/copy', 'SELECT host, path, target FROM Redirect ORDER BY path', [['host' => 'example.com', 'path' => '/copy', 'target' => '/new'], ['host' => 'example.com', 'path' => '/old', 'target' => '/new']]],
    'an association beside a scalar' => [Membership::class, '1~editor', 'role', 'viewer', 'SELECT note_id, role, label FROM Membership ORDER BY role', [['note_id' => 1, 'role' => 'editor', 'label' => 'Editors'], ['note_id' => 1, 'role' => 'viewer', 'label' => 'Editors']]],
    'a single string key' => [Ticket::class, 'T-1', 'code', 'T-2', 'SELECT code, title FROM Ticket ORDER BY code', [['code' => 'T-1', 'title' => 'Alpha'], ['code' => 'T-2', 'title' => 'Alpha']]],
]);

it('refuses a keyed modal whose beforeReplicaSaved() derives the key, since the key the modal mapped is checked first', function (string $class, string $recordKey, array $paths, string $rows, array $stored): void {
    replicateTakenKeyOriginal($class);

    $host = replicateTakenKeyMounted($class, $recordKey, 'replicateDerived');

    expect(replicateTakenKeyRefusal($host))->toBe(array_fill_keys($paths, ['This value is already used.']))
        ->and(replicateTakenKeyRows($rows))->toBe($stored);
})->with([
    'a scalar pair' => [Redirect::class, 'example=2Ecom~=2Fold', ['mountedActionsData.0.host', 'mountedActionsData.0.path'], 'SELECT host, path, target FROM Redirect', [['host' => 'example.com', 'path' => '/old', 'target' => '/new']]],
    'an association beside a scalar' => [Membership::class, '1~editor', ['mountedActionsData.0.role'], 'SELECT note_id, role, label FROM Membership', [['note_id' => 1, 'role' => 'editor', 'label' => 'Editors']]],
    'a single string key' => [Ticket::class, 'T-1', ['mountedActionsData.0.code'], 'SELECT code, title FROM Ticket', [['code' => 'T-1', 'title' => 'Alpha']]],
]);

it('leaves a key field disabled on edit enabled under a renamed EditAction, and answers a changed key under that field', function (): void {
    replicateTakenKeyOriginal(Ticket::class);

    $host = replicateTakenKeyMounted(Ticket::class, 'T-1', 'editRenamed');

    expect(replicateTakenKeyField($host, 'code')->isDisabled())->toBeFalse();

    $host->mountedActionsData[0]['code'] = 'T-9';

    expect(replicateTakenKeyRefusal($host))->toBe(['mountedActionsData.0.code' => ['This value cannot be changed.']])
        ->and(replicateTakenKeyRows('SELECT code, title FROM Ticket'))->toBe([['code' => 'T-1', 'title' => 'Alpha']]);
});

it('disables that key field under the plain EditAction', function (): void {
    replicateTakenKeyOriginal(Ticket::class);

    $host = replicateTakenKeyMounted(Ticket::class, 'T-1', 'edit');

    expect(replicateTakenKeyField($host, 'code')->isDisabled())->toBeTrue();
});

it('leaves a key field disabled on edit enabled in a replicate modal, and answers the unchanged key under it', function (): void {
    replicateTakenKeyOriginal(Ticket::class);

    $host = replicateTakenKeyMounted(Ticket::class, 'T-1', 'replicateLocked');

    expect(replicateTakenKeyField($host, 'code')->isDisabled())->toBeFalse()
        ->and(replicateTakenKeyRefusal($host))->toBe(['mountedActionsData.0.code' => ['This value is already used.']]);
});
