<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use ArrayObject;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use ReflectionProperty;
use Sylament\Actions\Tests\Fixtures\Doctrine\AnnexManager;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Actions\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Actions\Tests\Fixtures\Twig\Components\AnnexBareReplicateTable;
use Sylament\Actions\Tests\Fixtures\Twig\Components\AnnexTicketsTable;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\State\AssociationReader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The container's association reader replaced, before anything builds one, by
 * a subclass whose constructor takes an argument the service's does not — so
 * only a clone, never `new static(...)`, can rebind it — and which logs every
 * key-rule question it answers with the reader that answered. The create, edit
 * and replicate a table hosts over a second entity manager must ask those
 * questions of that subclass, bound to the manager they write through, never of
 * a reader they build themselves. `TableQueryManagerKeyTest`'s rig, in-process
 * on one host object for the reason `ReplicateActionTest` states; the reader
 * slot survives because no second request reboots the kernel.
 */

beforeEach(function (): void {
    $request = new Request();
    $request->setSession($this::getContainer()->get('session.factory')->createSession());

    $this::getContainer()->get(RequestStack::class)->push($request);
});

/**
 * @return array{AssociationReader, ArrayObject<int, array{string, AssociationReader}>}
 */
function reboundReaderInstall(): array
{
    /** @var ArrayObject<int, array{string, AssociationReader}> $log */
    $log = new ArrayObject();

    $spy = new class ($log, test()->getContainer()->get(EntityManagerInterface::class)) extends AssociationReader {
        /**
         * @param ArrayObject<int, array{string, AssociationReader}> $log
         */
        public function __construct(private readonly ArrayObject $log, EntityManagerInterface $entityManager)
        {
            parent::__construct($entityManager);
        }

        public function hasIncompleteAssignedIdentifier(object $entity): bool
        {
            $this->log[] = [__FUNCTION__, $this];

            return parent::hasIncompleteAssignedIdentifier($entity);
        }

        public function hasBlankAssignedIdentifier(object $record): bool
        {
            $this->log[] = [__FUNCTION__, $this];

            return parent::hasBlankAssignedIdentifier($record);
        }

        public function revertIdentifierChanges(object $record): array
        {
            $this->log[] = [__FUNCTION__, $this];

            return parent::revertIdentifierChanges($record);
        }

        public function findTakenIdentifiers(object $record): array
        {
            $this->log[] = [__FUNCTION__, $this];

            return parent::findTakenIdentifiers($record);
        }

        public function findBlankAssignedIdentifiers(object $record): array
        {
            $this->log[] = [__FUNCTION__, $this];

            return parent::findBlankAssignedIdentifiers($record);
        }
    };

    test()->getContainer()->set('sylament_schemas.association_reader', $spy);

    return [$spy, $log];
}

/**
 * Each logged question's name, once every reader that answered it is of the
 * spy's own class and bound to the manager the action writes through.
 *
 * @param ArrayObject<int, array{string, AssociationReader}> $log
 *
 * @return list<string>
 */
function reboundReaderQuestions(ArrayObject $log, AssociationReader $spy, EntityManagerInterface $writeManager): array
{
    $boundManager = new ReflectionProperty(AssociationReader::class, 'entityManager');

    foreach ($log as [$question, $reader]) {
        expect($reader)->toBeInstanceOf($spy::class)
            ->and($boundManager->getValue($reader))->toBe($writeManager);
    }

    return array_column($log->getArrayCopy(), 0);
}

function reboundReaderAnnexManager(): EntityManager
{
    $annex = test()->getContainer()->get(AnnexManager::class);
    assert($annex instanceof AnnexManager);

    return $annex->get();
}

function reboundReaderStoredTicket(): void
{
    $ticket = new Ticket();
    $ticket->setCode('T-1');
    $ticket->setTitle('Alpha');

    EntityManagerFactory::seed(reboundReaderAnnexManager(), $ticket);
}

/**
 * @param class-string<AnnexTicketsTable|AnnexBareReplicateTable> $host
 * @param array<string, mixed>                                   $context
 */
function reboundReaderMounted(string $host, string $action, array $context): AnnexTicketsTable|AnnexBareReplicateTable
{
    $component = test()->createLiveComponent($host)->component();
    assert($component instanceof AnnexTicketsTable || $component instanceof AnnexBareReplicateTable);

    $component->mountAction($action, [], $context);

    return $component;
}

/**
 * @return array<string, list<string>>
 */
function reboundReaderRefusal(AnnexTicketsTable $host): array
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
function reboundReaderRows(): array
{
    return reboundReaderAnnexManager()->getConnection()->fetchAllAssociative('SELECT code, title FROM Ticket ORDER BY code');
}

it('hands the reader installed in the container to the schema a mounted action builds', function (): void {
    [$spy] = reboundReaderInstall();
    reboundReaderStoredTicket();

    $host = reboundReaderMounted(AnnexTicketsTable::class, 'edit', ['table' => true, 'recordKey' => 'T-1']);

    expect($host->getMountedActionSchema(0)?->getAssociationReader())->toBe($spy);
});

it("asks the installed reader's class, bound to the table query's manager, for an edit that leaves the key alone", function (): void {
    [$spy, $log] = reboundReaderInstall();
    reboundReaderStoredTicket();

    $host = reboundReaderMounted(AnnexTicketsTable::class, 'edit', ['table' => true, 'recordKey' => 'T-1']);
    assert($host instanceof AnnexTicketsTable);
    $host->mountedActionsData[0]['title'] = 'Beta';
    $host->callMountedAction();

    expect(reboundReaderQuestions($log, $spy, reboundReaderAnnexManager()))->toContain('revertIdentifierChanges', 'findTakenIdentifiers')
        ->and(reboundReaderRows())->toBe([['code' => 'T-1', 'title' => 'Beta']]);
});

it("asks the installed reader's class, bound to the table query's manager, for a create on a key that database holds", function (): void {
    [$spy, $log] = reboundReaderInstall();
    reboundReaderStoredTicket();

    $host = reboundReaderMounted(AnnexTicketsTable::class, 'create', ['table' => true]);
    assert($host instanceof AnnexTicketsTable);
    $host->mountedActionsData[0]['code'] = 'T-1';
    $host->mountedActionsData[0]['title'] = 'Duplicate';

    expect(reboundReaderRefusal($host))->toBe(['mountedActionsData.0.code' => ['This value is already used.']])
        ->and(reboundReaderQuestions($log, $spy, reboundReaderAnnexManager()))->toContain('findTakenIdentifiers')
        ->and(reboundReaderRows())->toBe([['code' => 'T-1', 'title' => 'Alpha']]);
});

it("asks the installed reader's class, bound to the table query's manager, for a modal replicate left on its original's key", function (): void {
    [$spy, $log] = reboundReaderInstall();
    reboundReaderStoredTicket();

    $host = reboundReaderMounted(AnnexTicketsTable::class, 'replicate', ['table' => true, 'recordKey' => 'T-1']);
    assert($host instanceof AnnexTicketsTable);

    expect(reboundReaderRefusal($host))->toBe(['mountedActionsData.0.code' => ['This value is already used.']])
        ->and(reboundReaderQuestions($log, $spy, reboundReaderAnnexManager()))->toContain('findTakenIdentifiers')
        ->and(reboundReaderRows())->toBe([['code' => 'T-1', 'title' => 'Alpha']]);
});

it("asks the action's own reader, rebound to the table query's manager, for a replicate that opens no form", function (): void {
    [$spy, $log] = reboundReaderInstall();
    reboundReaderStoredTicket();

    $host = reboundReaderMounted(AnnexBareReplicateTable::class, 'replicate', ['table' => true, 'recordKey' => 'T-1']);

    expect(fn (): mixed => $host->callMountedAction())->toThrow(LogicException::class, sprintf('The [replicate] action built a replica of [%s] whose key another record already holds', Ticket::class))
        ->and(reboundReaderQuestions($log, $spy, reboundReaderAnnexManager()))->toContain('hasIncompleteAssignedIdentifier', 'findTakenIdentifiers')
        ->and(reboundReaderRows())->toBe([['code' => 'T-1', 'title' => 'Alpha']]);
});
