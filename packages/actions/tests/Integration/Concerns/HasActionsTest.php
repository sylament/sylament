<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\Exceptions\ActionNotResolvableException;
use Sylament\Actions\Tests\Fixtures\Twig\Components\ActionHost;
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
        ->toContain('data-sylament--support--modal-id-value="delete-modal"');
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
