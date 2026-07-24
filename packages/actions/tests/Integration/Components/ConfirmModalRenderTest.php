<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Components;

use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

it('renders the confirm modal composing the action modal config and mount/call wiring', function (): void {
    $html = (string) $this->createLiveComponent('RecordActionHost')
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['recordKey' => 1]])
        ->render();

    expect($html)
        ->toContain('sy-modal')
        ->toContain('Delete?')
        ->toContain('Confirm')
        ->toContain('Cancel')
        ->toContain('<svg')
        ->toContain('data-live-action-param="callMountedAction"')
        ->toContain('data-live-action-param="unmountAction"');
});
