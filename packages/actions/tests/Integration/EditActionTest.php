<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Note;
use Sylament\Actions\Tests\Fixtures\Twig\Components\RecordWriteHost;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The edit modal end to end, in-process on one host object: a `->call()` is a
 * fresh request that rebuilds the component and its cached actions, so the
 * mounted stack a case sets up would not be the one it then calls. The success
 * toast is a session flash — `Notification::send()` reads the session off the
 * `RequestStack` — so each case pushes a request carrying one, the way
 * `SylamentActionsBundleTest` does for the rig itself.
 */

beforeEach(function (): void {
    $request = new Request();
    $request->setSession($this::getContainer()->get('session.factory')->createSession());

    $this::getContainer()->get(RequestStack::class)->push($request);
});

function seededNote(string $title, ?string $body = null): Note
{
    $note = new Note();
    $note->setTitle($title);
    $note->setBody($body);

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist($note);
    $entityManager->flush();

    return $note;
}

it('fills the modal from the record the mount context named', function (): void {
    $note = seededNote('Alpha', 'the body');

    $host = $this->createLiveComponent(RecordWriteHost::class)->component();
    $host->mountAction('edit', [], ['recordKey' => (string) $note->getId()]);

    expect($host->getMountedActionData(0))->toBe(['title' => 'Alpha', 'body' => 'the body'])
        ->and($host->getMountedAction()?->getModalHeading())->toBe('Edit Alpha');
});

it('rewrites the filled data through mutateRecordDataUsing', function (): void {
    $note = seededNote('Alpha', 'the body');

    $host = $this->createLiveComponent(RecordWriteHost::class)->component();
    $host->mountAction('editMutated', [], ['recordKey' => (string) $note->getId()]);

    expect($host->getMountedActionData(0))->toBe(['title' => 'draft: Alpha', 'body' => 'the body']);
});

it('saves the edited state onto the same record and unmounts', function (): void {
    $note = seededNote('Alpha', 'the body');
    $id = $note->getId();

    $host = $this->createLiveComponent(RecordWriteHost::class)->component();
    $host->mountAction('edit', [], ['recordKey' => (string) $id]);
    $host->mountedActionsData[0]['title'] = 'Beta';
    $host->callMountedAction();

    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $entityManager->clear();

    expect($entityManager->find(Note::class, $id)?->getTitle())->toBe('Beta')
        ->and($host->mountedActions)->toBe([]);
});

it('fails fast at mount when nothing binds a record to fill', function (): void {
    $host = $this->createLiveComponent(RecordWriteHost::class)->component();

    expect(fn (): mixed => $host->mountAction('edit'))
        ->toThrow(LogicException::class, 'fills its form from a record but none is bound');
});

it('fills from an array record but refuses to write one', function (): void {
    $host = $this->createLiveComponent(RecordWriteHost::class)->component();
    $host->mountAction('editArray');

    expect($host->getMountedActionData(0))->toBe(['__key' => '1', 'title' => 'Loose', 'body' => null])
        ->and(fn (): mixed => $host->callMountedAction())
        ->toThrow(LogicException::class, 'writes a record but no entity is bound');
});
