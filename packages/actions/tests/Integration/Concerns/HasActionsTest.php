<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Concerns;

use stdClass;
use Sylament\Actions\Action;
use Sylament\Actions\Exceptions\ActionNotResolvableException;
use Sylament\Actions\Tests\Fixtures\Twig\Components\ActionHost;
use Sylament\Actions\Tests\Fixtures\Twig\Components\ActionSchemaHost;
use Sylament\Actions\Tests\Fixtures\Twig\Components\RecordActionHost;
use Sylament\Actions\Tests\Fixtures\Twig\Components\RecordBoundFormHost;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The AC2 spike: an argument-carrying #[LiveAction] mounts an Action descriptor
 * into the #[LiveProp], opens the AC1 modal, and a second #[LiveAction] runs the
 * Action's closure — the mount -> modal -> call round-trip on a synthetic host.
 * Mirrors forms' LiveFieldTest (InteractsWithLiveComponents; ->call() runs the
 * LiveAction server-side, so the client click is not simulated).
 */

it('mounts an action into the live prop and renders its modal', function (): void {
    $component = $this->createLiveComponent('ActionHost')
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => []]);

    expect($component->component()->mountedActions)->toHaveCount(1)
        ->and($component->component()->mountedActions[0]['name'])->toBe('delete');

    expect((string) $component->render())
        ->toContain('sy-modal')
        ->toMatch('/data-sylament--support--modal-id-value="sy-[0-9a-f]{8}-action-0"/');
});

it('runs the mounted closure and unmounts on callMountedAction', function (): void {
    $component = $this->createLiveComponent('ActionHost')
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => []])
        ->call('callMountedAction');

    expect($component->component()->called)->toBeTrue()
        ->and($component->component()->mountedActions)->toBe([]);
});

it('renders the trigger wired to the mountAction live action', function (): void {
    expect((string) $this->createLiveComponent('ActionHost')->render())
        ->toContain('data-action="live#action"')
        ->toContain('data-live-action-param="mountAction"')
        ->toContain('data-live-name-param="delete"');
});

it('runs a non-confirming action immediately on mount and leaves nothing mounted', function (): void {
    $component = $this->createLiveComponent('ActionHost')
        ->call('mountAction', ['name' => 'notify', 'arguments' => [], 'context' => []]);

    expect($component->component()->notified)->toBeTrue()
        ->and($component->component()->mountedActions)->toBe([]);
});

it('renders no modal for a non-confirming action', function (): void {
    $html = (string) $this->createLiveComponent('ActionHost')
        ->call('mountAction', ['name' => 'notify', 'arguments' => [], 'context' => []])
        ->render();

    expect($html)->not->toContain('sy-modal');
});

it('mounts an action with only a modal heading and opens its modal instead of auto-running', function (): void {
    $component = $this->createLiveComponent('ActionHost')
        ->call('mountAction', ['name' => 'headed', 'arguments' => [], 'context' => []]);

    expect($component->component()->notified)->toBeFalse()
        ->and($component->component()->mountedActions)->toHaveCount(1);

    expect((string) $component->render())
        ->toContain('sy-modal')
        ->toContain('Headed?');
});

it('returns the auto-run result from mountAction', function (): void {
    expect($this->createLiveComponent('ActionHost')->component()->mountAction('notify'))->toBeTrue();
});

it('returns the same instance for repeated getAction() calls', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();

    expect($host->getAction('delete'))->toBe($host->getAction('delete'));
});

it('serves the mounted action from the same cache as getAction()', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $trigger = $host->getAction('delete');
    $host->mountAction('delete');

    // requiresConfirmation() keeps it mounted; identity must hold across resolution paths.
    expect($host->getMountedAction())->toBe($trigger);
});

it('binds the live component when caching an action directly', function (): void {
    $host = new ActionHost();
    $action = Action::make('external');

    expect($host->cacheAction($action))->toBe($action)
        ->and($action->getLiveComponent())->toBe($host);
});

it('resolves a bare method with an Action return type', function (): void {
    expect(new ActionHost()->getAction('publish'))->toBeInstanceOf(Action::class);
});

it('prefers the {name}Action method over the bare method', function (): void {
    expect(new ActionHost()->getAction('delete')?->getName())->toBe('delete');
});

it('does not resolve a bare method without a declared Action return type', function (): void {
    $host = new ActionHost();

    expect($host->getAction('untyped'))->toBeNull()
        ->and($host->getAction('notAnAction'))->toBeNull();
});

it('throws when a factory method returns a non-Action at runtime', function (): void {
    new ActionHost()->getAction('liar');
})->throws(ActionNotResolvableException::class);

it('unmounts instead of crashing when mounting an unresolvable action', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();

    expect($host->mountAction('liar'))->toBeNull()
        ->and($host->mountedActions)->toBe([]);
});

it('unmounts a disabled action without calling it', function (): void {
    $component = $this->createLiveComponent('ActionHost')
        ->call('mountAction', ['name' => 'blocked', 'arguments' => [], 'context' => []]);

    expect($component->component()->notified)->toBeFalse()
        ->and($component->component()->mountedActions)->toBe([]);
});

it('appends a second frame and resolves it through the parent modal actions', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->mountAction('edit');
    $host->mountAction('confirmDelete');

    expect($host->mountedActions)->toHaveCount(2)
        ->and($host->mountedActions[1]['name'])->toBe('confirmDelete')
        ->and($host->getMountedAction()?->getName())->toBe('confirmDelete')
        ->and($host->getMountedAction()?->getNestingIndex())->toBe(1)
        ->and($host->getMountedAction(0)?->getName())->toBe('edit')
        ->and($host->getMountedActions())->toHaveCount(2);
});

it('pops the pushed frame when a nested name is not a parent modal action', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->mountAction('edit');

    expect($host->mountAction('delete'))->toBeNull()
        ->and($host->mountedActions)->toHaveCount(1)
        ->and($host->mountedActions[0]['name'])->toBe('edit');
});

it('serves both an explicit index read and the top read from one warm stack', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->mountAction('edit');
    $host->mountAction('confirmDelete');

    expect($host->getMountedAction(0)?->getName())->toBe('edit')
        ->and($host->getMountedAction()?->getName())->toBe('confirmDelete');
});

it('re-resolves a prefix on a cold cache without losing the top frame', function (): void {
    $host = new ActionHost();
    $host->mountedActions = [
        ['name' => 'edit', 'arguments' => [], 'context' => []],
        ['name' => 'confirmDelete', 'arguments' => [], 'context' => []],
    ];

    // Cold cache: the index-0 read caches only the [edit] prefix (Filament's
    // Arr::take behaviour), so the following top read must re-resolve the full stack.
    expect($host->getMountedAction(0)?->getName())->toBe('edit')
        ->and($host->getMountedAction()?->getName())->toBe('confirmDelete')
        ->and($host->getMountedActions())->toHaveCount(2);
});

it('resolves a mounted action without a name to null instead of throwing', function (): void {
    $host = new ActionHost();
    $host->mountedActions = [['name' => '', 'arguments' => [], 'context' => []]];

    expect($host->getMountedAction())->toBeNull();
});

it('merges frame arguments into the resolved action during resolution', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->mountAction('edit', ['key' => 'value']);

    expect($host->getMountedAction()?->getArguments())->toBe(['key' => 'value']);
});

it('pops one frame for cancelParentActions false and clears all for true', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->mountAction('edit');
    $host->mountAction('confirmDelete');

    $host->unmountAction(false);

    expect($host->mountedActions)->toHaveCount(1);

    $host->mountAction('confirmDelete');
    $host->unmountAction(true);

    expect($host->mountedActions)->toBe([]);
});

it('cancels to a named parent and closes that parent too', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->mountAction('edit');
    $host->mountAction('deeper');
    $host->mountAction('deepest');

    $host->unmountAction('deeper');

    expect($host->mountedActions)->toHaveCount(1)
        ->and($host->mountedActions[0]['name'])->toBe('edit');
});

it('consults the mounted action for a null cancel argument', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->mountAction('edit');
    $host->mountAction('confirmDelete');
    $host->getMountedAction()?->cancelParentActions(true);

    $host->unmountAction();

    expect($host->mountedActions)->toBe([]);
});

it('truncates the resolved cache alongside the stack', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->mountAction('edit');
    $host->mountAction('confirmDelete');
    $host->getMountedActions();

    $host->unmountAction(false);

    expect($host->getMountedAction()?->getName())->toBe('edit')
        ->and($host->getMountedActions())->toHaveCount(1);
});

it('merges call arguments into the top action and restores them afterwards', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->mountAction('edit', ['a' => 1]);

    $edit = $host->getMountedAction();

    $host->callMountedAction(['b' => 2]);

    expect($host->argumentsSeen)->toBe(['a' => 1, 'b' => 2])
        ->and($edit?->getArguments())->toBe(['a' => 1]);
});

/*
 * The above test alone cannot tell "merges into the top action" apart from the
 * deleted `mergeArguments($this->mountedActions[0]['arguments'] ?? [])` bug: with
 * a single frame, index 0 and the top are the same object. This variant pushes a
 * second, argument-less frame on top of `edit` so the two are provably distinct —
 * a regression of that exact line would leak edit's `a` into the child's arguments.
 */
it('merges call arguments into the top action specifically, not the root frame', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->mountAction('edit', ['a' => 1]);
    $host->mountAction('confirmDelete');

    $edit = $host->getMountedAction(0);
    $top = $host->getMountedAction();
    $top?->action(function (array $arguments) use ($host): void {
        $host->argumentsSeen = $arguments;
    });

    $host->callMountedAction(['b' => 2]);

    expect($host->argumentsSeen)->toBe(['b' => 2])
        ->and($top?->getArguments())->toBe([])
        ->and($edit?->getArguments())->toBe(['a' => 1]);
});

/*
 * Fix-wave regression: prepareModalAction() (CanOpenModal.php) propagates a
 * parent's record onto a declared modal action that has none of its own —
 * `if (! $action->hasRecord()) { $action->record($this->getRecord()); }` — the
 * first time the parent's modal actions are resolved. callMountedAction() used
 * to overwrite that unconditionally with getMountedActionRecord(), which is
 * always null for a child frame (a child's Action::getContext() emits no
 * recordKey), wiping the inherited record before the child's own closure saw it.
 */
it('keeps the record a child frame inherited from its parent when the child is called', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->mountAction('edit');
    $host->getAction('edit')?->record('ENTITY');
    $host->mountAction('confirmDelete');

    $recordSeen = null;
    $host->getMountedAction()?->action(function (mixed $record) use (&$recordSeen): void {
        $recordSeen = $record;
    });

    $host->callMountedAction();

    expect($recordSeen)->toBe('ENTITY');
});

/*
 * The rollback flag reaches the catch arms and the pipeline finalises the (absent)
 * transaction without ever resolving a connection: transactions are off by default,
 * so begin/commit/rollBack are no-ops and no manager registry is needed. The
 * transactional halves of these paths are pinned against the Doctrine rig in tables.
 */
it('cancels with the rollback flag and still unmounts without a transaction', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->getAction('notify')?->action(function (Action $action): void {
        $action->cancel(true);
    });

    $host->mountAction('notify');

    expect($host->notified)->toBeFalse()
        ->and($host->mountedActions)->toBe([]);
});

it('halts with the rollback flag and stays mounted without a transaction', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->getAction('headed')?->action(function (Action $action): void {
        $action->halt(true);
    });

    $host->mountAction('headed');
    $host->callMountedAction();

    expect($host->notified)->toBeFalse()
        ->and($host->mountedActions)->toHaveCount(1);
});

it('does not unmount when the stack was replaced mid-call', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->mountAction('replacer');

    expect($host->mountedActions)->toHaveCount(1)
        ->and($host->mountedActions[0]['name'])->toBe('headed');
});

it('replaceMountedAction clears the whole stack before mounting', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->mountAction('edit');
    $host->mountAction('confirmDelete');

    $host->replaceMountedAction('headed');

    expect($host->mountedActions)->toHaveCount(1)
        ->and($host->mountedActions[0]['name'])->toBe('headed')
        ->and($host->getMountedAction()?->getNestingIndex())->toBe(0);
});

it('scopes modal ids to the host and nesting index, stable across reads', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->mountAction('edit');
    $host->mountAction('confirmDelete');

    expect($host->getMountedModalId())->toMatch('/^sy-[0-9a-f]{8}-action-1$/')
        ->and($host->getMountedModalId(0))->toMatch('/^sy-[0-9a-f]{8}-action-0$/')
        ->and($host->getMountedModalId())->toBe($host->getMountedModalId())
        ->and($host->actionsHostId)->not->toBeNull()
        ->and($this->createLiveComponent('ActionHost')->component()->getActionsHostId())
            ->not->toBe($host->getActionsHostId());
});

/*
 * $actionsHostId is a #[LiveProp] with a same-named getter (getActionsHostId()) —
 * the same shape that made getMountedActions() collide with $mountedActions in
 * Task 1: PropertyAccessor::getValue() prefers a same-named getter over the raw
 * property, so LiveComponentHydrator::dehydrate() calls getActionsHostId() rather
 * than reading the property directly (harmless here, unlike Task 1's collision,
 * since the getter returns the same string type dehydrate expects). This test
 * exercises a real HTTP round trip through ->call() (dehydrate -> DOM -> next
 * request -> hydrate) rather than calling methods directly on one in-memory
 * object, so it would catch the id silently resetting between requests even
 * though the two prior assertions could not.
 */
it('keeps actionsHostId stable across a real hydrate/dehydrate round trip', function (): void {
    $component = $this->createLiveComponent('ActionHost')
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => []]);

    $firstId = $component->component()->getActionsHostId();

    $component = $component->call('unmountAction');

    expect($component->component()->getActionsHostId())->toBe($firstId);
});

it('opens only the top frame when the child does not overlay its parents', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->mountAction('edit');
    $host->mountAction('confirmDelete');

    expect($host->isMountedActionModalOpen(0))->toBeFalse()
        ->and($host->isMountedActionModalOpen(1))->toBeTrue();
});

it('keeps parents open under an overlaying child', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->mountAction('edit');
    $host->mountAction('overlayChild');

    expect($host->isMountedActionModalOpen(0))->toBeTrue()
        ->and($host->isMountedActionModalOpen(1))->toBeTrue();
});

/*
 * A footer action with no modal heading opens no modal, so mountAction() routes it
 * straight into callMountedAction() (HasActions.php:105-107); haltingChild's closure
 * then throws Halt, which callMountedAction() catches and returns from without
 * reaching unmountAction() — so the frame stays on the stack (the same halt-keeps-it-
 * mounted contract HasActionsLifecycleTest pins). A frame with no modal covers
 * nothing, so it must not hide its parent: the parent must stay open.
 */
it('keeps a parent open when a non-modal child halts on top of it', function (): void {
    $host = $this->createLiveComponent('ActionHost')->component();
    $host->mountAction('edit');
    $host->mountAction('haltingChild');

    expect($host->isMountedActionModalOpen(0))->toBeTrue()
        ->and($host->isMountedActionModalOpen(1))->toBeFalse();
});

/*
 * The record-key half of the seed: a frame's key reaches the host resolver only
 * when the frame is the host's own, and the record it resolves is bound while
 * the action resolves rather than when it is called.
 */

it('mounts and calls a component-owned action on a record-bound form without a resolver', function (): void {
    $host = $this->createLiveComponent(RecordBoundFormHost::class)->component();
    $action = $host->getSchema('schema')->getAction('ping');

    $host->mountAction('ping', [], $action?->getContext() ?? []);
    $host->callMountedAction();

    expect($host->getMountedActions())->toBe([])
        ->and($host->getAction('ping'))->toBeNull();
});

it('binds a root frame resolved record at resolve time, before any fill', function (): void {
    $host = $this->createLiveComponent(RecordActionHost::class)->component();

    $host->mountAction('delete', [], ['recordKey' => 1]);

    expect($host->getMountedAction()?->getRecord())->toBe('Ada');
});

it('binds a resolved record whose string cast is blank', function (): void {
    $host = $this->createLiveComponent(RecordActionHost::class)->component();

    $host->mountAction('delete', [], ['recordKey' => 3]);

    expect($host->getMountedAction()?->getRecord())->toBe($host->getRow(3));

    $host->callMountedAction();

    expect($host->deleted)->toBe([$host->getRow(3)]);
});

it('clears a create-shaped action record once the stack empties', function (): void {
    $host = $this->createLiveComponent(ActionSchemaHost::class)->component();
    $host->mountAction('clearing');

    expect($host->getAction('clearing')?->getRecord())->toBeInstanceOf(stdClass::class);

    $host->callMountedAction();

    expect($host->mountedActions)->toBe([])
        ->and($host->getAction('clearing')?->getRecord())->toBeNull();
});
