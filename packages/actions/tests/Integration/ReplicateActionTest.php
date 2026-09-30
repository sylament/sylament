<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Exception\EntityMissingAssignedId;
use LogicException;
use Sylament\Actions\ReplicateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\AssignedNote;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Bulb;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Cover;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Graft;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Membership;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Note;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Planter;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Roster;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Scion;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Seedling;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Trellis;
use Sylament\Actions\Tests\Fixtures\Twig\Components\GreenhouseHost;
use Sylament\Actions\Tests\Fixtures\Twig\Components\RecordWriteHost;
use Sylament\Schemas\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * In-process on one host object, for the reason `EditActionTest` states: a
 * `->call()` is a fresh request that rebuilds the component and its cached
 * actions, so the mounted stack a case sets up would not be the one it calls.
 * The success toast the write body ends in reads the session off the
 * `RequestStack`, so each case pushes a request carrying one.
 */

beforeEach(function (): void {
    $request = new Request();
    $request->setSession($this::getContainer()->get('session.factory')->createSession());

    $this::getContainer()->get(RequestStack::class)->push($request);
});

function replicatedNotes(string $title): array
{
    return test()->getContainer()->get(EntityManagerInterface::class)->getRepository(Note::class)->findBy(['title' => $title]);
}

function noteToReplicate(string $title = 'Alpha', ?string $body = 'the body'): Note
{
    $note = new Note();
    $note->setTitle($title);
    $note->setBody($body);

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist($note);
    $entityManager->flush();

    return $note;
}

it('replicates through the modal: the form is pre-filled from the copy and the submit lands a second row', function (): void {
    $note = noteToReplicate();
    $id = $note->getId();

    $host = $this->createLiveComponent(RecordWriteHost::class)->component();
    $host->mountAction('replicate', [], ['recordKey' => (string) $id]);

    expect($host->getMountedActionData(0))->toBe(['title' => 'Alpha (copy)'])
        ->and($host->getMountedAction()?->getModalHeading())->toBe('Replicate Alpha');

    $host->callMountedAction();

    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $replica = $entityManager->getRepository(Note::class)->findOneBy(['title' => 'Alpha (copy)']);

    expect($replica)->toBeInstanceOf(Note::class)
        ->and($replica?->getId())->not->toBe($id)
        ->and($replica?->getBody())->toBe('the body')
        ->and($entityManager->find(Note::class, $id)?->getTitle())->toBe('Alpha')
        ->and($host->mountedActions)->toBe([]);
});

it('replicates without a modal and still mutates, handing the saved replica to after()', function (): void {
    $note = noteToReplicate('Beta', 'kept');

    $host = $this->createLiveComponent(RecordWriteHost::class)->component();
    $host->mountAction('replicateBare', [], ['recordKey' => (string) $note->getId()]);

    expect($host->getMountedActionData(0))->toBe([]);

    $host->callMountedAction();

    $replica = replicatedNotes('Beta (copy)')[0] ?? null;

    expect(replicatedNotes('Beta'))->toHaveCount(1)
        ->and($replica)->toBeInstanceOf(Note::class)
        ->and($replica?->getBody())->toBe('kept')
        ->and($host->replicatedId)->toBe($replica?->getId());
});

it('hands beforeReplicaSaved the unsaved replica', function (): void {
    $note = noteToReplicate('Gamma');

    $host = $this->createLiveComponent(RecordWriteHost::class)->component();
    $host->mountAction('replicateBare', [], ['recordKey' => (string) $note->getId()]);

    $action = $host->getMountedAction();
    $seen = [];

    expect($action)->toBeInstanceOf(ReplicateAction::class);

    $action?->beforeReplicaSaved(function (object $replica) use (&$seen): void {
        $seen[] = $replica instanceof Note ? $replica->getId() : 'not a note';
    });

    $host->callMountedAction();

    expect($seen)->toBe([null])
        ->and($host->replicatedId)->not->toBeNull();
});

/**
 * One flushed original of an assigned-identifier flavour, keyed as its literal
 * in each case below spells it.
 *
 * @param class-string $class
 */
function gardenOriginal(string $class): object
{
    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $record = match ($class) {
        AssignedNote::class => (static function (): AssignedNote {
            $note = new AssignedNote();
            $note->setId(1);
            $note->setTitle('Alpha');

            return $note;
        })(),
        Bulb::class => (static function (): Bulb {
            $bulb = new Bulb();
            $bulb->setName('tulip-1');
            $bulb->setBloom('red');

            return $bulb;
        })(),
        Cover::class => (static function () use ($entityManager): Cover {
            $roster = new Roster();
            $roster->setTitle('owner');

            $entityManager->persist($roster);

            $cover = new Cover();
            $cover->setRoster($roster);
            $cover->setNote('front');

            return $cover;
        })(),
        Graft::class => (static function () use ($entityManager): Graft {
            $note = new Note();
            $note->setTitle('owner');

            $entityManager->persist($note);

            $graft = new Graft();
            $graft->setNote($note);
            $graft->setLabel('Rootstock');

            return $graft;
        })(),
        Membership::class => (static function () use ($entityManager): Membership {
            $note = new Note();
            $note->setTitle('owner');

            $entityManager->persist($note);

            $membership = new Membership();
            $membership->setNote($note);
            $membership->setRole('editor');
            $membership->setLabel('Editors');

            return $membership;
        })(),
        Planter::class => (static function (): Planter {
            $planter = new Planter();
            $planter->setBed('north');
            $planter->setSlot('a1');
            $planter->setCrop('basil');

            return $planter;
        })(),
        Redirect::class => (static function (): Redirect {
            $redirect = new Redirect();
            $redirect->setHost('example.com');
            $redirect->setPath('/old');
            $redirect->setTarget('/new');

            return $redirect;
        })(),
        Seedling::class => (static function (): Seedling {
            $seedling = new Seedling();
            $seedling->setTag('basil-1');
            $seedling->setVariety('basil');

            return $seedling;
        })(),
        Ticket::class => (static function (): Ticket {
            $ticket = new Ticket();
            $ticket->setCode('T-1');
            $ticket->setTitle('Alpha');

            return $ticket;
        })(),
        Trellis::class => (static function () use ($entityManager): Trellis {
            $note = new Note();
            $note->setTitle('owner');

            $roster = new Roster();
            $roster->setTitle('crew');

            $entityManager->persist($note);
            $entityManager->persist($roster);

            $trellis = new Trellis();
            $trellis->setNote($note);
            $trellis->setRoster($roster);
            $trellis->setLabel('Espalier');

            return $trellis;
        })(),
    };

    $entityManager->persist($record);
    $entityManager->flush();

    return $record;
}

function gardenRowCount(string $table): int
{
    return (int) test()->getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchOne('SELECT COUNT(*) FROM ' . $table);
}

/**
 * @return list<array<string, mixed>>
 */
function gardenRows(string $sql): array
{
    return test()->getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchAllAssociative($sql);
}

/**
 * @param class-string $class
 */
function gardenRefusal(string $action, string $class): string
{
    return sprintf('The [%s] action built a replica of [%s] whose identifier is incomplete, so it cannot be saved as a new record. Put every identifier field the copy leaves unset in its schema() — enabled and dehydrated — or set it in beforeReplicaSaved().', $action, $class);
}

/**
 * @param class-string $class
 */
function gardenTakenRefusal(string $action, string $class): string
{
    return sprintf('The [%s] action built a replica of [%s] whose key another record already holds, so it cannot be saved as a new record. Give the copy a key of its own: ask for it in schema(), or set it in beforeReplicaSaved() — an identifier association the copy keeps is left out with excludeAttributes().', $action, $class);
}

/**
 * @return array<string, list<string>>
 */
function gardenValidationErrors(GreenhouseHost $host): array
{
    try {
        $host->callMountedAction();
    } catch (ValidationException $validationException) {
        return $validationException->getErrors();
    }

    test()->fail('Expected the replicate to be refused with a ValidationException.');
}

/**
 * A second membership of the original's note, on the role a hook moves a copy to.
 */
function gardenViewer(bool $storedOnly): void
{
    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $note = $entityManager->find(Note::class, 1);
    assert($note instanceof Note);

    $viewer = new Membership();
    $viewer->setNote($note);
    $viewer->setRole('viewer');
    $viewer->setLabel('Viewers');

    $entityManager->persist($viewer);
    $entityManager->flush();

    if ($storedOnly) {
        $entityManager->clear();
    }
}

it('refuses by name, before persisting it, a replica whose key is still incomplete, writing nothing', function (string $class, string $key, string $action, string $table): void {
    gardenOriginal($class);

    $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => $class])->component();
    $host->mountAction($action, [], ['recordKey' => $key]);

    expect(fn (): mixed => $host->callMountedAction())->toThrow(LogicException::class, gardenRefusal($action, $class))
        ->and(gardenRowCount($table))->toBe(1)
        ->and($this::getContainer()->get(EntityManagerInterface::class)->isOpen())->toBeTrue();
})->with([
    'a scalar pair with no modal' => [Redirect::class, 'example=2Ecom~=2Fold', 'replicateBare', 'Redirect'],
    'a scalar pair whose modal names no key field' => [Redirect::class, 'example=2Ecom~=2Fold', 'replicatePayload', 'Redirect'],
    'a note and a role with no modal' => [Membership::class, '1~editor', 'replicateBare', 'Membership'],
    'a note and a role whose modal names no key field' => [Membership::class, '1~editor', 'replicatePayload', 'Membership'],
    'a single derived identity with no modal' => [Cover::class, '1', 'replicateBare', 'Cover'],
    'a single derived identity whose modal has no key field to name' => [Cover::class, '1', 'replicate', 'Cover'],
]);

it("refuses by name, before persisting it, a copy that carries its original's key, writing nothing", function (string $class, string $key, string $table): void {
    gardenOriginal($class);

    $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => $class])->component();
    $host->mountAction('replicateBare', [], ['recordKey' => $key]);

    expect(fn (): mixed => $host->callMountedAction())->toThrow(LogicException::class, gardenTakenRefusal('replicateBare', $class))
        ->and(gardenRowCount($table))->toBe(1)
        ->and($this::getContainer()->get(EntityManagerInterface::class)->isOpen())->toBeTrue();
})->with([
    'a key made only of associations, which the copy keeps whole' => [Trellis::class, '1~1', 'Trellis'],
    'an association beside a scalar half still on its default' => [Graft::class, '1~rootstock', 'Graft'],
]);

it('saves a copy of a key made only of associations once one is left out and the hook sets another, as the refusal advises', function (): void {
    gardenOriginal(Trellis::class);

    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $second = new Roster();
    $second->setTitle('second crew');

    $entityManager->persist($second);
    $entityManager->flush();

    $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => Trellis::class])->component();
    $host->mountAction('replicateRehomed', [], ['recordKey' => '1~1']);
    $host->callMountedAction();

    expect(gardenRows('SELECT note_id, roster_id, label FROM Trellis ORDER BY roster_id'))->toBe([
        ['note_id' => 1, 'roster_id' => 1, 'label' => 'Espalier'],
        ['note_id' => 1, 'roster_id' => 2, 'label' => 'Espalier'],
    ]);
});

it('answers a payload-only modal over a key made only of associations as a record error, writing nothing', function (): void {
    gardenOriginal(Trellis::class);

    $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => Trellis::class])->component();
    $host->mountAction('replicatePayload', [], ['recordKey' => '1~1']);

    expect(gardenValidationErrors($host))->toBe(['mountedActionsData.0' => ['This value is already used.']])
        ->and(gardenRowCount('Trellis'))->toBe(1);
});

it("refuses by name a key a beforeReplicaSaved() hook moves onto another record's, with no modal", function (bool $storedOnly): void {
    gardenOriginal(Membership::class);
    gardenViewer($storedOnly);

    $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => Membership::class])->component();
    $host->mountAction('replicateHooked', [], ['recordKey' => '1~editor']);

    expect(fn (): mixed => $host->callMountedAction())->toThrow(LogicException::class, gardenTakenRefusal('replicateHooked', Membership::class))
        ->and(gardenRows('SELECT note_id, role, label FROM Membership ORDER BY role'))->toBe([
            ['note_id' => 1, 'role' => 'editor', 'label' => 'Editors'],
            ['note_id' => 1, 'role' => 'viewer', 'label' => 'Viewers'],
        ]);
})->with([
    'the holder managed' => [false],
    'the holder only stored' => [true],
]);

it("answers in the modal a key a beforeReplicaSaved() hook moves onto another record's", function (): void {
    gardenOriginal(Membership::class);
    gardenViewer(false);

    $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => Membership::class])->component();
    $host->mountAction('replicateKeyedHooked', [], ['recordKey' => '1~editor']);
    $host->mountedActionsData[0]['role'] = 'author';

    expect(gardenValidationErrors($host))->toBe(['mountedActionsData.0.role' => ['This value is already used.']])
        ->and(gardenRowCount('Membership'))->toBe(2);
});

it('saves the key a beforeReplicaSaved() hook sets after a keyed modal, once it is free', function (): void {
    gardenOriginal(Membership::class);

    $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => Membership::class])->component();
    $host->mountAction('replicateKeyedHooked', [], ['recordKey' => '1~editor']);
    $host->mountedActionsData[0]['role'] = 'author';
    $host->callMountedAction();

    expect(gardenRows('SELECT note_id, role, label FROM Membership ORDER BY role'))->toBe([
        ['note_id' => 1, 'role' => 'editor', 'label' => 'Editors'],
        ['note_id' => 1, 'role' => 'viewer', 'label' => 'Editors'],
    ]);
});

it('refuses by name a blank single string key a prePersist listener on the event manager leaves blank', function (): void {
    gardenOriginal(Ticket::class);
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $eventManager = $entityManager->getEventManager();
    $listener = new class {
        public function prePersist(PrePersistEventArgs $args): void
        {
        }
    };

    $eventManager->addEventListener(Events::prePersist, $listener);

    try {
        $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => Ticket::class])->component();
        $host->mountAction('replicateBare', [], ['recordKey' => 'T-1']);

        expect(fn (): mixed => $host->callMountedAction())->toThrow(LogicException::class, gardenRefusal('replicateBare', Ticket::class));
    } finally {
        $eventManager->removeEventListener(Events::prePersist, $listener);
    }

    expect(gardenRows('SELECT code, title FROM Ticket'))->toBe([
        ['code' => 'T-1', 'title' => 'Alpha'],
    ]);
});

it('refuses by name, after every prePersist callback has run, a replica whose key is still incomplete, and takes it back out of the unit of work', function (string $action): void {
    gardenOriginal(Planter::class);
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => Planter::class])->component();
    $host->mountAction($action, [], ['recordKey' => 'north~a1']);

    expect(fn (): mixed => $host->callMountedAction())->toThrow(LogicException::class, gardenRefusal($action, Planter::class));

    $entityManager->flush();

    expect(gardenRows('SELECT bed, slot, crop, season FROM Planter'))->toBe([
        ['bed' => 'north', 'slot' => 'a1', 'crop' => 'basil', 'season' => 'spring'],
    ])->and($entityManager->isOpen())->toBeTrue();
})->with([
    'no modal' => ['replicateBare'],
    'a modal naming no key field' => ['replicatePayload'],
    'a modal naming one half of the key' => ['replicate'],
]);

it('refuses by name a blank-default single string key on the first replicate, where it wrote a blank row', function (): void {
    gardenOriginal(Ticket::class);
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => Ticket::class])->component();
    $host->mountAction('replicateBare', [], ['recordKey' => 'T-1']);

    expect(fn (): mixed => $host->callMountedAction())->toThrow(LogicException::class, gardenRefusal('replicateBare', Ticket::class));

    $entityManager->flush();

    expect(gardenRows('SELECT code, title FROM Ticket'))->toBe([
        ['code' => 'T-1', 'title' => 'Alpha'],
    ]);
});

it('saves a replica whose blank single string key a prePersist listener on the event manager fills', function (): void {
    gardenOriginal(Ticket::class);
    $eventManager = $this::getContainer()->get(EntityManagerInterface::class)->getEventManager();
    $listener = new class {
        public function prePersist(PrePersistEventArgs $args): void
        {
            $ticket = $args->getObject();

            if ($ticket instanceof Ticket && $ticket->getCode() === '') {
                $ticket->setCode('T-2');
            }
        }
    };

    $eventManager->addEventListener(Events::prePersist, $listener);

    try {
        $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => Ticket::class])->component();
        $host->mountAction('replicateBare', [], ['recordKey' => 'T-1']);
        $host->callMountedAction();
    } finally {
        $eventManager->removeEventListener(Events::prePersist, $listener);
    }

    expect(gardenRows('SELECT code, title FROM Ticket ORDER BY code'))->toBe([
        ['code' => 'T-1', 'title' => 'Alpha'],
        ['code' => 'T-2', 'title' => 'Alpha'],
    ]);
});

it('saves a replica whose blank single string key a mapped prePersist entity listener fills', function (): void {
    $scion = new Scion();
    $scion->setTag('apple-1');
    $scion->setVariety('apple');

    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist($scion);
    $entityManager->flush();

    $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => Scion::class])->component();
    $host->mountAction('replicateBare', [], ['recordKey' => 'apple-1']);
    $host->callMountedAction();

    expect(gardenRows('SELECT tag, variety FROM Scion ORDER BY tag'))->toBe([
        ['tag' => 'apple-1', 'variety' => 'apple'],
        ['tag' => 'apple-graft', 'variety' => 'apple'],
    ]);
});

it('refuses by name a replica whose prePersist callback leaves its single string key blank, writing nothing and leaving it scheduled', function (): void {
    gardenOriginal(Bulb::class);
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => Bulb::class])->component();
    $host->mountAction('replicateBare', [], ['recordKey' => 'tulip-1']);

    expect(fn (): mixed => $host->callMountedAction())->toThrow(LogicException::class, gardenRefusal('replicateBare', Bulb::class))
        ->and(gardenRows('SELECT name, bloom, season FROM Bulb'))->toBe([
            ['name' => 'tulip-1', 'bloom' => 'red', 'season' => 'autumn'],
        ])
        ->and($entityManager->getUnitOfWork()->getScheduledEntityInsertions())->toHaveCount(1)
        ->each->toBeInstanceOf(Bulb::class);
});

it('keeps the parent and takes the scalar half of the key from the modal', function (): void {
    gardenOriginal(Membership::class);

    $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => Membership::class])->component();
    $host->mountAction('replicate', [], ['recordKey' => '1~editor']);

    expect($host->getMountedActionData(0))->toBe(['role' => 'editor', 'label' => 'Editors']);

    $host->mountedActionsData[0]['role'] = 'viewer';
    $host->callMountedAction();

    expect(gardenRows('SELECT note_id, role, label FROM Membership ORDER BY role'))->toBe([
        ['note_id' => 1, 'role' => 'editor', 'label' => 'Editors'],
        ['note_id' => 1, 'role' => 'viewer', 'label' => 'Editors'],
    ]);
});

it('writes a scalar pair taken whole from the modal', function (): void {
    gardenOriginal(Redirect::class);

    $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => Redirect::class])->component();
    $host->mountAction('replicate', [], ['recordKey' => 'example=2Ecom~=2Fold']);

    expect($host->getMountedActionData(0))->toBe(['host' => 'example.com', 'path' => '/old', 'target' => '/new']);

    $host->mountedActionsData[0]['path'] = '/copy';
    $host->callMountedAction();

    expect(gardenRows('SELECT host, path, target FROM Redirect ORDER BY path'))->toBe([
        ['host' => 'example.com', 'path' => '/copy', 'target' => '/new'],
        ['host' => 'example.com', 'path' => '/old', 'target' => '/new'],
    ]);
});

it('lets beforeReplicaSaved() supply the key half the copy leaves unset', function (): void {
    gardenOriginal(Membership::class);

    $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => Membership::class])->component();
    $host->mountAction('replicateHooked', [], ['recordKey' => '1~editor']);
    $host->callMountedAction();

    expect(gardenRows('SELECT note_id, role, label FROM Membership ORDER BY role'))->toBe([
        ['note_id' => 1, 'role' => 'editor', 'label' => 'Editors'],
        ['note_id' => 1, 'role' => 'viewer', 'label' => 'Editors'],
    ]);
});

it('saves a replica whose key a prePersist callback fills, the key being read after the callback', function (): void {
    gardenOriginal(Seedling::class);

    $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => Seedling::class])->component();
    $host->mountAction('replicateBare', [], ['recordKey' => 'basil-1']);
    $host->callMountedAction();

    expect(gardenRows('SELECT tag, variety FROM Seedling ORDER BY tag'))->toBe([
        ['tag' => 'basil-1', 'variety' => 'basil'],
        ['tag' => 'basil-sprout', 'variety' => 'basil'],
    ]);
});

it('leaves a single assigned int that nothing sets to Doctrine, as before', function (): void {
    gardenOriginal(AssignedNote::class);

    $host = $this->createLiveComponent(GreenhouseHost::class, ['recordClass' => AssignedNote::class])->component();
    $host->mountAction('replicateBare', [], ['recordKey' => '1']);

    expect(fn (): mixed => $host->callMountedAction())->toThrow(EntityMissingAssignedId::class)
        ->and(gardenRows('SELECT id, title FROM AssignedNote'))->toBe([
            ['id' => 1, 'title' => 'Alpha'],
        ]);
});
