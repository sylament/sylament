<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Tests\Fixtures\Doctrine\AnnexManager;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Actions\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Actions\Tests\Fixtures\Twig\Components\AnnexTicketsTable;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Testing\Doctrine\RecordingMiddleware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * A table whose query() runs on a second entity manager over a database of its
 * own: the create, edit and replicate it hosts write through that manager, so
 * the two record-key rules read that manager's unit of work and database. The
 * container's database holds a ticket on the same key in the edit cases and
 * none in the create and replicate ones. In-process on one host object, for
 * the reason `ReplicateActionTest` states; each case pushes a request carrying
 * a session for the success toast.
 */

beforeEach(function (): void {
    $request = new Request();
    $request->setSession($this::getContainer()->get('session.factory')->createSession());

    $this::getContainer()->get(RequestStack::class)->push($request);
});

function annexKeyManager(): EntityManager
{
    $annex = test()->getContainer()->get(AnnexManager::class);
    assert($annex instanceof AnnexManager);

    return $annex->get();
}

function annexKeyDefaultManager(): EntityManager
{
    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    assert($entityManager instanceof EntityManager);

    return $entityManager;
}

function annexKeyStoredTicket(EntityManager $entityManager, string $title): void
{
    $ticket = new Ticket();
    $ticket->setCode('T-1');
    $ticket->setTitle($title);

    EntityManagerFactory::seed($entityManager, $ticket);
}

/**
 * @param array<string, mixed> $context
 */
function annexKeyMounted(string $action, array $context): AnnexTicketsTable
{
    $host = test()->createLiveComponent(AnnexTicketsTable::class)->component();
    assert($host instanceof AnnexTicketsTable);

    $host->mountAction($action, [], $context);

    return $host;
}

/**
 * @return array<string, list<string>>
 */
function annexKeyRefusal(AnnexTicketsTable $host): array
{
    try {
        $host->callMountedAction();
    } catch (ValidationException $validationException) {
        return $validationException->getErrors();
    }

    test()->fail('Expected the action to be refused with a ValidationException.');
}

/**
 * @return list<array<string, mixed>>
 */
function annexKeyRows(EntityManager $entityManager): array
{
    return $entityManager->getConnection()->fetchAllAssociative('SELECT code, title FROM Ticket ORDER BY code');
}

it("saves an edit that leaves the key alone through the table query's manager, whatever the container's database holds on that key", function (): void {
    annexKeyStoredTicket(annexKeyManager(), 'Alpha');
    annexKeyStoredTicket(annexKeyDefaultManager(), 'Default');

    $host = annexKeyMounted('edit', ['table' => true, 'recordKey' => 'T-1']);

    expect($host->getMountedActionData(0))->toBe(['code' => 'T-1', 'title' => 'Alpha']);

    $host->mountedActionsData[0]['title'] = 'Beta';

    $recorder = $this::getContainer()->get(RecordingMiddleware::class);
    assert($recorder instanceof RecordingMiddleware);
    $recorder->reset();

    $host->callMountedAction();

    expect($recorder->statements())->toBe([])
        ->and(annexKeyRows(annexKeyManager()))->toBe([['code' => 'T-1', 'title' => 'Beta']])
        ->and(annexKeyRows(annexKeyDefaultManager()))->toBe([['code' => 'T-1', 'title' => 'Default']])
        ->and($host->mountedActions)->toBe([]);
});

it("refuses an edit that changes the key under its field, putting it back and writing nothing through the table query's manager", function (): void {
    annexKeyStoredTicket(annexKeyManager(), 'Alpha');
    annexKeyStoredTicket(annexKeyDefaultManager(), 'Default');

    $host = annexKeyMounted('edit', ['table' => true, 'recordKey' => 'T-1']);
    $host->mountedActionsData[0]['code'] = 'T-2';
    $host->mountedActionsData[0]['title'] = 'Beta';

    expect(annexKeyRefusal($host))->toBe(['mountedActionsData.0.code' => ['This value cannot be changed.']])
        ->and(annexKeyManager()->find(Ticket::class, 'T-1')?->getCode())->toBe('T-1')
        ->and(annexKeyRows(annexKeyManager()))->toBe([['code' => 'T-1', 'title' => 'Alpha']])
        ->and(annexKeyRows(annexKeyDefaultManager()))->toBe([['code' => 'T-1', 'title' => 'Default']]);
});

it("refuses a create on a key only the table query's database holds under its field, that manager left open", function (): void {
    annexKeyStoredTicket(annexKeyManager(), 'Alpha');

    $host = annexKeyMounted('create', ['table' => true]);
    $host->mountedActionsData[0]['code'] = 'T-1';
    $host->mountedActionsData[0]['title'] = 'Duplicate';

    expect(annexKeyRefusal($host))->toBe(['mountedActionsData.0.code' => ['This value is already used.']])
        ->and(annexKeyManager()->isOpen())->toBeTrue()
        ->and(annexKeyRows(annexKeyManager()))->toBe([['code' => 'T-1', 'title' => 'Alpha']])
        ->and(annexKeyRows(annexKeyDefaultManager()))->toBe([]);
});

it("refuses a replica left on a key only the table query's database holds under its field, that manager left open", function (): void {
    annexKeyStoredTicket(annexKeyManager(), 'Alpha');

    $host = annexKeyMounted('replicate', ['table' => true, 'recordKey' => 'T-1']);

    expect($host->getMountedActionData(0))->toBe(['code' => 'T-1', 'title' => 'Alpha'])
        ->and(annexKeyRefusal($host))->toBe(['mountedActionsData.0.code' => ['This value is already used.']])
        ->and(annexKeyManager()->isOpen())->toBeTrue()
        ->and(annexKeyRows(annexKeyManager()))->toBe([['code' => 'T-1', 'title' => 'Alpha']])
        ->and(annexKeyRows(annexKeyDefaultManager()))->toBe([]);
});
