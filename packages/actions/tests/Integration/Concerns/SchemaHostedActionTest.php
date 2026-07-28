<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Concerns;

use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * End-to-end mount round-trip for a schema-hosted action: the Section header
 * action renders its trigger with the schema mount context, mountAction resolves
 * it back through `context['schemaComponent']` into the confirm modal, and
 * callMountedAction runs the closure and unmounts. The context collapses to the
 * root schema name because the Section keys itself non-inheritably.
 */

it('renders the header-action trigger with the schema mount context', function (): void {
    $html = (string) $this->createLiveComponent('SchemaSectionHost')->render();

    expect($html)->toContain('data-live-name-param="publish"')
        ->toContain('data-live-context-param="{&quot;schemaComponent&quot;:&quot;schema&quot;}"');
});

it('mounts the schema-hosted action from its context and renders the confirm modal', function (): void {
    $html = (string) $this->createLiveComponent('SchemaSectionHost')
        ->call('mountAction', ['name' => 'publish', 'arguments' => [], 'context' => ['schemaComponent' => 'schema']])
        ->render();

    expect($html)->toContain('<dialog')
        ->toMatch('/id="sy-[0-9a-f]{8}-action-0"/')
        ->toContain('sy-modal-heading')
        ->toContain('>Publish</h2>');
});

it('runs the schema-hosted action closure on confirm and unmounts', function (): void {
    $component = $this->createLiveComponent('SchemaSectionHost')
        ->call('mountAction', ['name' => 'publish', 'arguments' => [], 'context' => ['schemaComponent' => 'schema']])
        ->call('callMountedAction');

    expect((string) $component->render())->toContain('data-published')
        ->and($component->component()->published)->toBeTrue()
        ->and($component->component()->mountedActions)->toBe([]);
});
