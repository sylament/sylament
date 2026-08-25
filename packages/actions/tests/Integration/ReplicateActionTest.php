<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\ReplicateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Note;
use Sylament\Actions\Tests\Fixtures\Twig\Components\RecordWriteHost;
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
