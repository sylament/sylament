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
        ->toContain('data-live-action-param="unmountAction"')
        ->toContain('sy-btn');
});

it('renders extra footer actions between submit and cancel', function (): void {
    $html = (string) $this->createLiveComponent('RecordActionHost')
        ->call('mountAction', ['name' => 'briefed', 'arguments' => [], 'context' => []])
        ->render();

    $submitPosition = mb_strpos($html, 'data-live-action-param="callMountedAction"');
    $extraPosition = mb_strpos($html, '>Extra<');
    $cancelPosition = mb_strpos($html, 'data-live-action-param="unmountAction"', $submitPosition);

    expect($submitPosition)->not->toBeFalse()
        ->and($extraPosition)->not->toBeFalse()
        ->and($submitPosition)->toBeLessThan($extraPosition)
        ->and($extraPosition)->toBeLessThan($cancelPosition);
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

it('passes the chrome config through to the modal', function (): void {
    $html = (string) $this->createLiveComponent('RecordActionHost')
        ->call('mountAction', ['name' => 'sideSheet', 'arguments' => [], 'context' => []])
        ->render();

    expect($html)->toContain('sy-modal-slide-over')
        ->toContain('sy-modal-slide-over-from-end')
        ->toContain('sy-modal-has-sticky-header')
        ->toContain('sy-modal-has-sticky-footer')
        ->toContain('sy-modal-close-btn');
});

it('centers and reverses the confirmation modal footer', function (): void {
    $html = (string) $this->createLiveComponent('RecordActionHost')
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['recordKey' => 1]])
        ->render();

    $footerActionsPosition = mb_strpos($html, 'sy-modal-footer-actions');

    expect($footerActionsPosition)->not->toBeFalse();

    $cancelPosition = mb_strpos($html, 'data-live-action-param="unmountAction"', $footerActionsPosition);
    $submitPosition = mb_strpos($html, 'data-live-action-param="callMountedAction"', $footerActionsPosition);

    expect($html)->toContain('sy-align-center')
        ->and($cancelPosition)->not->toBeFalse()
        ->and($submitPosition)->not->toBeFalse()
        ->and($cancelPosition)->toBeLessThan($submitPosition);
});

it('unmounts the action when the modal reports a client-side dismissal', function (): void {
    $html = (string) $this->createLiveComponent('RecordActionHost')
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['recordKey' => 1]])
        ->render();

    expect($html)->toContain('sylament--support--modal:dismissed->live#action');
});
