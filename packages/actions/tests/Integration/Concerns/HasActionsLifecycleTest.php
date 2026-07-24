<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Concerns;

use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * AC4 end-to-end confirm lifecycle on a synthetic host: mountAction resolves the
 * record from context['recordKey'] via the host resolveRecordUsing, callMountedAction
 * runs the closure with the $record and unmounts. Halt keeps the action mounted;
 * Cancel clears it (Decision 2). ->call() runs the LiveActions server-side.
 */

it('resolves the record, runs the closure with it, and unmounts', function (): void {
    $component = $this->createLiveComponent('RecordActionHost')
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['recordKey' => 1]])
        ->call('callMountedAction');

    expect($component->component()->deleted)->toBe(['Ada'])
        ->and($component->component()->mountedActions)->toBe([]);
});

it('keeps the action mounted when the closure halts', function (): void {
    $component = $this->createLiveComponent('RecordActionHost')
        ->call('mountAction', ['name' => 'halt', 'arguments' => [], 'context' => []])
        ->call('callMountedAction');

    expect($component->component()->mountedActions)->toHaveCount(1);
});

it('clears the mounted action when the closure cancels', function (): void {
    $component = $this->createLiveComponent('RecordActionHost')
        ->call('mountAction', ['name' => 'cancel', 'arguments' => [], 'context' => []])
        ->call('callMountedAction');

    expect($component->component()->mountedActions)->toBe([]);
});
