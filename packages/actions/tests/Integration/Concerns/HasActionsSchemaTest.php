<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Concerns;

use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The end-to-end mounted-action form pipeline on a live host. The two
 * host-shape guards this pipeline also carries — a schema-bearing action
 * mounted on a host that is not an `InteractsWithSchemas`, and the schema-less
 * control that must still mount there — need no kernel, so they live in
 * `tests/Unit/Concerns/HasActionsSchemaHostTest.php` instead.
 */

it('seeds an empty data frame for the action it just mounted', function (): void {
    $host = $this->createLiveComponent('ActionSchemaHost')->component();
    $host->mountAction('note');

    // The bag invariant: the frame exists in the props before anything can
    // render, even for an action that has no schema to fill it.
    expect($host->mountedActionsData)->toBe([0 => []]);
});

it('fills the mounted action schema into its own data frame', function (): void {
    $host = $this->createLiveComponent('ActionSchemaHost')->component();
    $host->mountAction('edit');

    expect($host->mountedActionsData)->toBe([0 => ['title' => null]])
        ->and($host->getMountedActionData())->toBe(['title' => null])
        ->and($host->getMountedActionSchema()?->getStatePath())->toBe('mountedActionsData.0');
});

it('opens a modal for an action whose only modal content is its schema', function (): void {
    $host = $this->createLiveComponent('ActionSchemaHost')->component();
    $host->mountAction('edit');

    expect($host->mountedActionHasSchema())->toBeTrue()
        ->and($host->mountedActionShouldOpenModal())->toBeTrue()
        ->and($host->mountedActions)->toHaveCount(1)
        ->and($host->savedData)->toBe([]);
});

it('renders the mounted schema fields bound to the mounted action data frame', function (): void {
    $html = (string) $this->createLiveComponent('ActionSchemaHost')
        ->call('mountAction', ['name' => 'edit', 'arguments' => [], 'context' => []])
        ->render();

    expect($html)->toContain('data-model="norender|mountedActionsData.0.title"');
});

it('lands a client write on the data frame in the called action data', function (): void {
    $component = $this->createLiveComponent('ActionSchemaHost')
        ->call('mountAction', ['name' => 'edit', 'arguments' => [], 'context' => []])
        ->set('mountedActionsData.0.title', 'Ada')
        ->call('callMountedAction');

    expect($component->component()->savedData)->toBe(['title' => 'Ada'])
        ->and($component->component()->mountedActions)->toBe([]);
});

it('fires the form-filled hooks around the fill', function (): void {
    $host = $this->createLiveComponent('ActionSchemaHost')->component();
    $host->mountAction('edit');

    expect($host->hookLog)->toBe(['beforeFormFilled', 'afterFormFilled'])
        ->and($host->mountedActionsData)->toBe([0 => ['title' => null]]);
});

it('does not fire the form-filled hooks for an action with no schema', function (): void {
    $host = $this->createLiveComponent('ActionSchemaHost')->component();
    $host->mountAction('note');

    expect($host->hookLog)->toBe([]);
});

it('gives a nested frame its own data frame and state path', function (): void {
    $host = $this->createLiveComponent('ActionSchemaHost')->component();
    $host->mountAction('edit');
    $host->mountAction('nested');

    expect(array_keys($host->mountedActionsData))->toBe([0, 1])
        ->and($host->getMountedActionSchema()?->getStatePath())->toBe('mountedActionsData.1')
        ->and($host->getMountedActionData())->toBe(['reason' => null])
        ->and($host->getMountedActionData(0))->toBe(['title' => null]);
});

it('pops the data frame and its cached schema when a frame unmounts', function (): void {
    $host = $this->createLiveComponent('ActionSchemaHost')->component();
    $host->mountAction('edit');
    $host->mountAction('nested');
    $host->unmountAction(cancelParentActions: false);

    expect(array_keys($host->mountedActionsData))->toBe([0])
        ->and($host->hasCachedSchema('mountedActionSchema1'))->toBeFalse()
        ->and($host->hasCachedSchema('mountedActionSchema0'))->toBeTrue();
});

/*
 * The bag is `writable: true`, so its key order and its holes are the client's
 * to choose; the stack is not. Popping by index *value* rather than by position
 * is what keeps a surviving frame's slot — whose absence is an uncaught
 * `TypeError` in `live_controller`'s `getDeepData()` for the whole component —
 * from being the one that gets dropped.
 */
it('drops exactly the frames the stack lost, whatever order the bag arrived in', function (): void {
    $host = $this->createLiveComponent('ActionSchemaHost')->component();
    $host->mountAction('edit');
    $host->mountAction('nested');
    $host->mountedActionsData = [1 => ['reason' => null], 0 => ['title' => null]];

    $host->unmountAction(cancelParentActions: false);

    expect($host->mountedActionsData)->toBe([0 => ['title' => null]]);
});

it('clears every data frame and cached schema when the mounted action is replaced', function (): void {
    $host = $this->createLiveComponent('ActionSchemaHost')->component();
    $host->mountAction('edit');
    $host->replaceMountedAction('note');

    // A surviving `mountedActionSchema0` would be `edit`'s, keyed to the frame
    // `note` now occupies — the exact staleness the purge exists to stop.
    expect($host->mountedActionsData)->toBe([0 => []])
        ->and($host->hasCachedSchema('mountedActionSchema0'))->toBeFalse();
});

/*
 * The two adjacent gaps (design §5.1, §5.2). Both are reachable only on a
 * request that is *not* a `mountAction()` call, which is why they need the
 * live round-trip rig rather than a unit fixture.
 */

it('fires afterStateUpdated for a live field inside a mounted-action schema', function (): void {
    // Two sets, as in schemas' LiveStateTest: the first fires the hook with a null
    // old value, the second with "Hello" — so `slug` holding "Hello" proves the hook
    // ran on a plain state round-trip, with the modal schema served from the cache
    // that `afterMountedActionsHydrated()` refills at hydrate.
    $component = $this->createLiveComponent('ModalSchemaHost')
        ->call('mountAction', ['name' => 'edit', 'arguments' => [], 'context' => []])
        ->set('mountedActionsData.0.title', 'Hello')
        ->set('mountedActionsData.0.title', 'World');

    expect($component->component()->mountedActionsData[0]['title'])->toBe('World')
        ->and($component->component()->mountedActionsData[0]['slug'])->toBe('Hello');
});

it('mounts an action declared inside a mounted-action schema as a nested frame', function (): void {
    $component = $this->createLiveComponent('ModalSchemaHost')
        ->call('mountAction', ['name' => 'edit', 'arguments' => [], 'context' => []])
        ->call('mountAction', ['name' => 'archive', 'arguments' => [], 'context' => ['schemaComponent' => 'mountedActionSchema0']]);

    expect($component->component()->mountedActions)->toHaveCount(2)
        ->and($component->component()->mountedActions[1]['name'])->toBe('archive')
        ->and($component->component()->archived)->toBeNull()
        ->and((string) $component->render())->toMatch('/id="sy-[0-9a-f]{8}-action-1"/');
});

/*
 * The two gaps compose: `archive` is reachable only through the frame-0 schema
 * cache, and it carries a form of its own. Its schema is therefore registered
 * only if `resolveActions()` writes each frame's entry *before* walking to the
 * next — the frame-1 descriptor is resolved by reading the entry frame 0 just
 * wrote. Registering the set after the walk resolves neither.
 */
it('fires afterStateUpdated for a live field one modal deeper', function (): void {
    $component = $this->createLiveComponent('ModalSchemaHost')
        ->call('mountAction', ['name' => 'edit', 'arguments' => [], 'context' => []])
        ->call('mountAction', ['name' => 'archive', 'arguments' => [], 'context' => ['schemaComponent' => 'mountedActionSchema0']])
        ->set('mountedActionsData.1.reason', 'Hello')
        ->set('mountedActionsData.1.reason', 'World');

    expect($component->component()->mountedActionsData[1]['reason'])->toBe('World')
        ->and($component->component()->mountedActionsData[1]['echo'])->toBe('Hello');
});

it('runs a mounted-action-schema action and returns to the parent frame', function (): void {
    $component = $this->createLiveComponent('ModalSchemaHost')
        ->call('mountAction', ['name' => 'edit', 'arguments' => [], 'context' => []])
        ->call('mountAction', ['name' => 'archive', 'arguments' => [], 'context' => ['schemaComponent' => 'mountedActionSchema0']])
        ->call('callMountedAction');

    expect($component->component()->archived)->toBe('archived')
        ->and($component->component()->mountedActions)->toHaveCount(1)
        ->and($component->component()->mountedActions[0]['name'])->toBe('edit')
        ->and((string) $component->render())->toContain('data-archived');
});
