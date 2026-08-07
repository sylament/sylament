<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * An action placed in a field-wrapper slot has to stay reachable by name, or a
 * click resolves nothing and silently does nothing. The resolution is generic to
 * all ten slots — a hint action is only the first consumer — and it is load-bearing
 * that the slot child schemas stay *unkeyed*: `HasComponents::getAction()` recurses
 * into a component's child schemas, but skips a keyed one unless the nested key
 * targets it, so keying the slots would break every slot-nested action at once
 * while every render test stayed green.
 */

it('resolves a slot-nested action by name through the host, the way a mount does', function (): void {
    // The mount payload a slot action's trigger emits: its name, and the field's
    // absolute key as the schema-component context.
    $component = $this->createLiveComponent('SlotActionForm', ['data' => ['title' => 'Draft']])
        ->call('mountAction', [
            'name' => 'clear',
            'arguments' => [],
            'context' => ['schemaComponent' => 'schema.title'],
        ]);

    expect($component->component()->slotActionRuns)->toBe(1);
});
