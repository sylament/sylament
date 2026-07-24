<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Concerns;

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
