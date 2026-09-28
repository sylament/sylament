<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\AssignedNote;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Spice;
use Sylament\Actions\Tests\Fixtures\Twig\Components\LarderCreateHost;
use Sylament\Actions\Tests\Fixtures\Twig\Components\RecordWriteHost;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Testing\Doctrine\RecordingMiddleware;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

uses(InteractsWithLiveComponents::class);

/*
 * A plain create over an assigned key, where the key the modal types is the
 * whole key. Real live requests with the reboot disabled, for the reasons
 * `CreateActionTest` states — and because the recorder that counts a create's
 * statements rides on the one container-wide manager both requests share. The
 * one in-process case reads what the schema was bound to while it mapped, which
 * only the host object that ran the create still holds; it pushes a request
 * carrying a session for the success toast, as `ReplicateActionTest` does.
 */

function createTakenKeyMounted(string $host, string $action): TestLiveComponent
{
    /** @var KernelBrowser $client */
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    return test()->createLiveComponent($host, [], client: $client)
        ->call('mountAction', ['name' => $action, 'arguments' => [], 'context' => []]);
}

function createTakenKeyRecorder(): RecordingMiddleware
{
    $recorder = test()->getContainer()->get(RecordingMiddleware::class);
    assert($recorder instanceof RecordingMiddleware);

    $recorder->reset();

    return $recorder;
}

/**
 * @param array<string, mixed> $arguments
 *
 * @return array<string, list<string>>
 */
function createTakenKeyRefusal(TestLiveComponent $component, array $arguments = []): array
{
    try {
        $component->call('callMountedAction', ['arguments' => $arguments]);
    } catch (ValidationException $validationException) {
        return $validationException->getErrors();
    }

    test()->fail('Expected the create to be refused with a ValidationException.');
}

function createTakenKeySeededNote(int $id, string $title): AssignedNote
{
    $note = new AssignedNote();
    $note->setId($id);
    $note->setTitle($title);

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist($note);
    $entityManager->flush();

    return $note;
}

/**
 * @return list<array<string, mixed>>
 */
function createTakenKeyRows(string $sql): array
{
    return test()->getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchAllAssociative($sql);
}

it('refuses a stored id under its field after one lookup, then creates a free one in the same modal', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $entityManager->detach(createTakenKeySeededNote(7, 'Stored'));

    $component = createTakenKeyMounted(LarderCreateHost::class, 'createNote')
        ->set('mountedActionsData.0.id', '7')
        ->set('mountedActionsData.0.title', 'Duplicate');

    $recorder = createTakenKeyRecorder();

    expect(createTakenKeyRefusal($component))->toBe(['mountedActionsData.0.id' => ['This value is already used.']])
        ->and($recorder->statements())->toBe(['SELECT t0.id AS id_1, t0.title AS title_2 FROM AssignedNote t0 WHERE t0.id = ?'])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and($component->component()->mountedActions)->toHaveCount(1);

    $recorder->reset();

    $component->set('mountedActionsData.0.id', '8')->call('callMountedAction');

    expect($recorder->statements())->toBe([
        'SELECT t0.id AS id_1, t0.title AS title_2 FROM AssignedNote t0 WHERE t0.id = ?',
        'BEGIN',
        'INSERT INTO AssignedNote (id, title) VALUES (?, ?)',
        'COMMIT',
    ])
        ->and(createTakenKeyRows('SELECT id, title FROM AssignedNote ORDER BY id'))->toBe([
            ['id' => 7, 'title' => 'Stored'],
            ['id' => 8, 'title' => 'Duplicate'],
        ])
        ->and($component->component()->mountedActions)->toBe([]);
});

it('refuses an id the identity map already holds without running a statement', function (): void {
    createTakenKeySeededNote(7, 'Managed');

    $component = createTakenKeyMounted(LarderCreateHost::class, 'createNote')
        ->set('mountedActionsData.0.id', '7')
        ->set('mountedActionsData.0.title', 'Duplicate');

    $recorder = createTakenKeyRecorder();

    expect(createTakenKeyRefusal($component))->toBe(['mountedActionsData.0.id' => ['This value is already used.']])
        ->and($recorder->statements())->toBe([])
        ->and($this::getContainer()->get(EntityManagerInterface::class)->isOpen())->toBeTrue()
        ->and(createTakenKeyRows('SELECT id, title FROM AssignedNote ORDER BY id'))->toBe([['id' => 7, 'title' => 'Managed']]);
});

it('refuses the key a create-another run just wrote when the next create repeats it', function (): void {
    $component = createTakenKeyMounted(LarderCreateHost::class, 'createNote')
        ->set('mountedActionsData.0.id', '9')
        ->set('mountedActionsData.0.title', 'First')
        ->call('callMountedAction', ['arguments' => ['another' => true]]);

    expect($component->component()->mountedActions)->toHaveCount(1)
        ->and($component->component()->getMountedActionData(0))->toBe(['id' => null, 'title' => null]);

    $component
        ->set('mountedActionsData.0.id', '9')
        ->set('mountedActionsData.0.title', 'Second');

    expect(createTakenKeyRefusal($component, ['another' => true]))->toBe(['mountedActionsData.0.id' => ['This value is already used.']])
        ->and(createTakenKeyRows('SELECT id, title FROM AssignedNote ORDER BY id'))->toBe([['id' => 9, 'title' => 'First']]);
});

it('answers a record failing its own constraint with that message alone, and its taken key on the next submit', function (): void {
    $spice = new Spice();
    $spice->setName('cumin');
    $spice->setLabel('Whole');

    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist($spice);
    $entityManager->flush();
    $entityManager->detach($spice);

    $component = createTakenKeyMounted(LarderCreateHost::class, 'createSpice')
        ->set('mountedActionsData.0.name', 'cumin')
        ->set('mountedActionsData.0.label', '');

    expect(createTakenKeyRefusal($component))->toBe(['mountedActionsData.0.label' => ['This value should not be blank.']]);

    $component->set('mountedActionsData.0.label', 'Ground');

    expect(createTakenKeyRefusal($component))->toBe(['mountedActionsData.0.name' => ['This value is already used.']])
        ->and(createTakenKeyRows('SELECT name, label FROM Spice'))->toBe([['name' => 'cumin', 'label' => 'Whole']]);
});

it('adds no statement to a create whose identifier is generated', function (): void {
    $component = createTakenKeyMounted(RecordWriteHost::class, 'create')
        ->set('mountedActionsData.0.title', 'Generated');

    $recorder = createTakenKeyRecorder();

    $component->call('callMountedAction');

    expect($recorder->statements())->toBe([
        'BEGIN',
        'INSERT INTO Note (title, body) VALUES (?, ?)',
        'COMMIT',
    ]);
});

it('maps a plain create with the schema bound to its class alone', function (): void {
    $request = new Request();
    $request->setSession($this::getContainer()->get('session.factory')->createSession());

    $this::getContainer()->get(RequestStack::class)->push($request);

    $host = $this->createLiveComponent(LarderCreateHost::class)->component();
    assert($host instanceof LarderCreateHost);

    $host->mountAction('createNote');
    $host->mountedActionsData[0] = ['id' => '11', 'title' => 'Unbound'];
    $host->callMountedAction();

    expect($host->bindingsWhileMapping)->toBe(['null'])
        ->and(createTakenKeyRows('SELECT id, title FROM AssignedNote ORDER BY id'))->toBe([['id' => 11, 'title' => 'Unbound']]);
});
