<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Note;
use Sylament\Actions\Tests\Fixtures\Twig\Components\HookedNoteHost;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The default writes of the three record actions refuse a key their
 * mutateDataUsing() hook adds and no component of the modal maps, and let the
 * other four shapes through: an overwritten mapped key, a key a client typed
 * into the data bag, a key a hidden component maps, and a using() override.
 * In-process on one host object, for the reason `ReplicateActionTest` states;
 * each case pushes a request carrying a session for the success toast.
 */

beforeEach(function (): void {
    $request = new Request();
    $request->setSession($this::getContainer()->get('session.factory')->createSession());

    $this::getContainer()->get(RequestStack::class)->push($request);
});

function hookedNoteSeed(): Note
{
    $note = new Note();
    $note->setTitle('Alpha');
    $note->setBody('original body');

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist($note);
    $entityManager->flush();

    return $note;
}

/**
 * @param array<string, mixed> $typed what the case types into the modal, a key no component maps included
 */
function hookedNoteMounted(string $seam, string $arm, array $typed): HookedNoteHost
{
    $context = $seam === 'create' ? [] : ['recordKey' => (string) hookedNoteSeed()->getId()];

    $host = test()->createLiveComponent(HookedNoteHost::class, ['arm' => $arm])->component();
    assert($host instanceof HookedNoteHost);

    $host->mountAction($seam, [], $context);

    foreach ($typed as $key => $value) {
        $host->mountedActionsData[0][$key] = $value;
    }

    return $host;
}

/**
 * @return list<array<string, mixed>>
 */
function hookedNoteRows(): array
{
    return test()->getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchAllAssociative('SELECT title, body FROM Note ORDER BY id');
}

it('refuses by name, writing nothing, a key and a nested key the hook adds that no component of the modal maps', function (string $seam, array $rows): void {
    $host = hookedNoteMounted($seam, 'adding', ['title' => 'beta']);

    expect(fn (): mixed => $host->callMountedAction())->toThrow(LogicException::class, sprintf(
        'The [%s] action added [body, meta.source] in mutateDataUsing(), but no component of its schema maps those paths, so the default write would drop them. Map each with a component — a Hidden one for a value the user should not type, with required(false) where the record would otherwise require it — or take the write over with using().',
        $seam,
    ))
        ->and(hookedNoteRows())->toBe($rows);
})->with([
    'create' => ['create', []],
    'edit' => ['edit', [['title' => 'Alpha', 'body' => 'original body']]],
    'replicate' => ['replicate', [['title' => 'Alpha', 'body' => 'original body']]],
]);

it('saves a mapped key the hook overwrites', function (string $seam, array $rows): void {
    hookedNoteMounted($seam, 'overwriting', ['title' => 'beta'])->callMountedAction();

    expect(hookedNoteRows())->toBe($rows);
})->with([
    'create' => ['create', [['title' => 'BETA', 'body' => null]]],
    'edit' => ['edit', [['title' => 'BETA', 'body' => 'original body']]],
    'replicate' => ['replicate', [['title' => 'Alpha', 'body' => 'original body'], ['title' => 'BETA', 'body' => 'original body']]],
]);

it('neither refuses nor writes a key a client typed into the data bag that no component maps', function (string $seam, array $rows): void {
    hookedNoteMounted($seam, 'injected', ['title' => 'beta', 'body' => 'from the client'])->callMountedAction();

    expect(hookedNoteRows())->toBe($rows);
})->with([
    'create' => ['create', [['title' => 'BETA', 'body' => null]]],
    'edit' => ['edit', [['title' => 'BETA', 'body' => 'original body']]],
    'replicate' => ['replicate', [['title' => 'Alpha', 'body' => 'original body'], ['title' => 'BETA', 'body' => 'original body']]],
]);

it('writes a key the hook adds that a hidden component of the modal maps', function (string $seam, array $rows): void {
    hookedNoteMounted($seam, 'hidden', ['title' => 'beta'])->callMountedAction();

    expect(hookedNoteRows())->toBe($rows);
})->with([
    'create' => ['create', [['title' => 'beta', 'body' => 'from the hook']]],
    'edit' => ['edit', [['title' => 'beta', 'body' => 'from the hook']]],
    'replicate' => ['replicate', [['title' => 'Alpha', 'body' => 'original body'], ['title' => 'beta', 'body' => 'from the hook']]],
]);

it('leaves a using() override unguarded, handing it the key the hook adds', function (string $seam, array $rows): void {
    hookedNoteMounted($seam, 'using', ['title' => 'beta'])->callMountedAction();

    expect(hookedNoteRows())->toBe($rows);
})->with([
    'create' => ['create', [['title' => 'beta', 'body' => 'from the hook']]],
    'edit' => ['edit', [['title' => 'beta', 'body' => 'from the hook']]],
    'replicate' => ['replicate', [['title' => 'Alpha', 'body' => 'original body'], ['title' => 'beta', 'body' => 'from the hook']]],
]);
