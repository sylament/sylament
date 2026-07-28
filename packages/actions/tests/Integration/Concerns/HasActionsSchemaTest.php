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
