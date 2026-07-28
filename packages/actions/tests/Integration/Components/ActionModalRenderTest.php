<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Components;

use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

it('renders the action modal composing the modal config and mount/call wiring', function (): void {
    $html = (string) $this->createLiveComponent('RecordActionHost')
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['recordKey' => 1]])
        ->render();

    expect($html)
        ->toContain('sy-modal')
        ->toContain('Delete?')
        ->toContain('Are you sure you would like to do this?')
        ->toContain('Confirm')
        ->toContain('Cancel')
        ->toContain('<svg')
        ->toContain('data-live-action-param="callMountedAction"')
        ->toContain('data-live-action-param="unmountAction"');
});

it('renders modal content and the content footer between description and footer', function (): void {
    $html = (string) $this->createLiveComponent('RecordActionHost')
        ->call('mountAction', ['name' => 'briefed', 'arguments' => [], 'context' => []])
        ->render();

    expect($html)
        ->toContain('<p class="test-modal-content">Body</p>')
        ->toContain('<p class="test-modal-content-footer">briefed</p>');

    $contentPosition = mb_strpos($html, 'test-modal-content');
    $contentFooterPosition = mb_strpos($html, 'test-modal-content-footer');
    $footerPosition = mb_strpos($html, 'sy-modal-footer');

    expect($contentPosition)->toBeLessThan($contentFooterPosition)
        ->and($contentFooterPosition)->toBeLessThan($footerPosition);
});
