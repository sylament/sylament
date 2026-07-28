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
        ->toContain('type="submit"')
        ->toContain('data-live-action-param="unmountAction"')
        ->toContain('sy-btn');
});

it('renders extra footer actions between submit and cancel', function (): void {
    $html = (string) $this->createLiveComponent('RecordActionHost')
        ->call('mountAction', ['name' => 'briefed', 'arguments' => [], 'context' => []])
        ->render();

    $submitPosition = mb_strpos($html, 'type="submit"');
    $extraPosition = mb_strpos($html, '>Extra<');
    $cancelPosition = mb_strpos($html, 'data-action="sylament--support--modal#dismiss"', $submitPosition);

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

    $cancelPosition = mb_strpos($html, 'data-action="sylament--support--modal#dismiss"', $footerActionsPosition);
    $submitPosition = mb_strpos($html, 'type="submit"', $footerActionsPosition);

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

/*
 * The form wrapper is gated on `hasFormWrapper()` alone — not on the action
 * having a schema — because `getModalSubmitAction()` branches on exactly that
 * one predicate: with a wrapper it makes the confirm button a bare
 * `type="submit"` and drops its click handler. Gating the window on a second
 * predicate would desynchronise the pair and leave a schema-less confirmation
 * modal with a submit button and nothing to submit to. Filament couples them
 * the same way (`action-modal.blade.php:27`).
 */
it('renders a schema-less confirmation modal as a submitting form with no schema markup', function (): void {
    $html = (string) $this->createLiveComponent('RecordActionHost')
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['recordKey' => 1]])
        ->render();

    expect($html)->toMatch('/<form[^>]*class="[^"]*sy-modal-window/')
        ->toContain('submit->live#action:prevent')
        ->toContain('data-live-action-param="callMountedAction"')
        ->not->toMatch('/<div[^>]*class="[^"]*sy-modal-window/')
        ->not->toContain('sy-sc')
        // The submit button reaches the server through the form and nothing
        // else: a click binding here would be `mountAction('submit')`.
        ->not->toContain('data-live-name-param="submit"');
});

it('renders the mounted action schema between the modal content and the content footer', function (): void {
    $html = (string) $this->createLiveComponent('FormModalHost')
        ->call('mountAction', ['name' => 'review', 'arguments' => [], 'context' => []])
        ->render();

    expect($html)->toContain('Reason required')
        ->toContain('sy-sc-text');

    $bodyPosition = mb_strpos($html, 'test-review-body');
    $schemaPosition = mb_strpos($html, 'Reason required');
    $outroPosition = mb_strpos($html, 'test-review-outro');

    expect($bodyPosition)->not->toBeFalse()
        ->and($outroPosition)->not->toBeFalse()
        ->and($bodyPosition)->toBeLessThan($schemaPosition)
        ->and($schemaPosition)->toBeLessThan($outroPosition);
});

it('renders the modal window as a form submitting the mounted action when the schema is form-wrapped', function (): void {
    $html = (string) $this->createLiveComponent('FormModalHost')
        ->call('mountAction', ['name' => 'review', 'arguments' => [], 'context' => []])
        ->render();

    expect($html)->toMatch('/<form[^>]*class="[^"]*sy-modal-window/')
        ->toContain('submit->live#action:prevent')
        ->toContain('data-live-action-param="callMountedAction"')
        ->not->toMatch('/<div[^>]*class="[^"]*sy-modal-window/');
});

it('keeps the modal window a div when the action opts out of the form wrapper', function (): void {
    $html = (string) $this->createLiveComponent('FormModalHost')
        ->call('mountAction', ['name' => 'unwrapped', 'arguments' => [], 'context' => []])
        ->render();

    expect($html)->toContain('Reason required')
        ->toMatch('/<div[^>]*class="[^"]*sy-modal-window/')
        ->not->toContain('<form')
        ->not->toContain('submit->live#action:prevent');
});
