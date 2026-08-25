<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Note;
use Sylament\Actions\Tests\Fixtures\Twig\Components\RecordWriteHost;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

uses(InteractsWithLiveComponents::class);

/*
 * These cases run as real live requests rather than in-process on one host
 * object, because the success notification is a session flash: `send()` reads
 * the session off the `RequestStack`, so an in-process `callMountedAction()`
 * throws `SessionNotFoundException` before it can toast. Nothing here is bound
 * in-process either — the record class rides the `recordClass` LiveProp — so
 * the round trip loses none of the state the run needs. Kernel reboot is
 * disabled for the reason the panels page tests disable it: the fixture
 * database lives in memory on a container service, so a reboot between two
 * requests would hand the next one a freshly-migrated, empty one.
 */

/**
 * @param array<string, mixed> $data
 */
function mountedCreateAction(string $name, array $data = []): TestLiveComponent
{
    /** @var KernelBrowser $client */
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    return test()->createLiveComponent(RecordWriteHost::class, $data, client: $client)
        ->call('mountAction', ['name' => $name, 'arguments' => [], 'context' => []]);
}

function createdNote(string $title): ?Note
{
    return test()->getContainer()->get(EntityManagerInterface::class)->getRepository(Note::class)->findOneBy(['title' => $title]);
}

it('creates a record from the modal, toasts and unmounts', function (): void {
    $component = mountedCreateAction('create')
        ->set('mountedActionsData.0.title', 'Alpha')
        ->call('callMountedAction');

    expect(createdNote('Alpha'))->toBeInstanceOf(Note::class)
        ->and($component->component()->mountedActions)->toBe([])
        ->and($component->getDispatchedBrowserEvent($component->render(), 'sylament:notification'))->not->toBeNull();
});

it('applies mutateDataUsing before mapping and redirects on success', function (): void {
    $response = mountedCreateAction('createWithHook')
        ->set('mountedActionsData.0.title', 'alpha')
        ->call('callMountedAction')
        ->response();

    expect(createdNote('ALPHA'))->toBeInstanceOf(Note::class)
        ->and(createdNote('alpha'))->toBeNull()
        ->and($response->isRedirection())->toBeTrue()
        ->and($response->headers->get('Location'))->toBe('/done');
});

it('creates another: stays mounted, resets the form, keeps a preserved field, still toasts', function (): void {
    $component = mountedCreateAction('createPreserving')
        ->set('mountedActionsData.0.title', 'Alpha')
        ->set('mountedActionsData.0.body', 'kept')
        ->call('callMountedAction', ['arguments' => ['another' => true]]);

    $note = createdNote('Alpha');
    $render = $component->render();

    expect($note)->toBeInstanceOf(Note::class)
        ->and($note?->getBody())->toBe('kept')
        ->and($component->component()->mountedActions)->toHaveCount(1)
        ->and($component->component()->getMountedActionData(0))->toBe(['title' => null, 'body' => 'kept'])
        ->and($component->getDispatchedBrowserEvent($render, 'reset-schema-component-state'))->not->toBeNull()
        ->and($component->getDispatchedBrowserEvent($render, 'sylament:notification'))->not->toBeNull();
});

it('fails fast when nothing names the record class', function (): void {
    $component = mountedCreateAction('create', ['recordClass' => null])
        ->set('mountedActionsData.0.title', 'Alpha');

    expect(fn (): TestLiveComponent => $component->call('callMountedAction'))
        ->toThrow(LogicException::class, 'creates a record but nothing names its class');
});
