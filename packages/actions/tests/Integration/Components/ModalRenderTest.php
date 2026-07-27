<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Components;

/**
 * Covers the support-level native-`<dialog>` `<twig:SylamentSupport:Modal>`
 * primitive: closed-by-default chrome plus the `sylament--support--modal`
 * Stimulus wiring the actions layer drives in AC2.
 */
function renderModal(string $template): string
{
    return test()->getContainer()->get('twig')->createTemplate($template)->render();
}

it('renders a closed dialog with the modal controller wiring', function (): void {
    $html = renderModal('<twig:SylamentSupport:Modal id="delete-modal" heading="Delete?" />');

    expect($html)->toContain('<dialog')
        ->toContain('class="sy sy-modal sy-modal-md"')
        ->toContain('id="delete-modal"')
        ->toContain('data-controller="sylament--support--modal"')
        ->toContain('data-sylament--support--modal-id-value="delete-modal"')
        ->toContain('sylament-open-modal@window-&gt;sylament--support--modal#open')
        ->toContain('sylament-close-modal@window-&gt;sylament--support--modal#close')
        ->toContain('close-&gt;sylament--support--modal#restoreFocus')
        ->toContain('Delete?')
        ->not->toMatch('/<dialog[^>]*\sopen[\s>]/');
});

it('declares its open state for the controller, defaulting to closed', function (): void {
    expect(renderModal('<twig:SylamentSupport:Modal id="m" />'))
        ->not->toContain('data-sylament--support--modal-open-value');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" :open="true" />'))
        ->toContain('data-sylament--support--modal-open-value="true"');
});

it('renders header pieces only when filled', function (): void {
    expect(renderModal('<twig:SylamentSupport:Modal id="m" />'))
        ->not->toContain('sy-modal-header')
        ->not->toContain('sy-modal-content')
        ->not->toContain('sy-modal-footer');

    $full = renderModal('<twig:SylamentSupport:Modal id="m" heading="Heading" description="Details" icon="heroicons:x-mark" iconColor="danger" />');

    expect($full)->toContain('sy-modal-header')
        ->toContain('sy-modal-heading')
        ->toContain('Heading')
        ->toContain('Details')
        ->toContain('<svg')
        ->toContain('sy-color sy-color-danger')
        ->toContain('aria-labelledby="m-heading"')
        ->toContain('aria-describedby="m-description"');
});

it('renders the body slot in the content container', function (): void {
    expect(renderModal('<twig:SylamentSupport:Modal id="m">Body text</twig:SylamentSupport:Modal>'))
        ->toContain('sy-modal-content')
        ->toContain('Body text');
});

it('renders the footer slot', function (): void {
    $html = renderModal('<twig:SylamentSupport:Modal id="m"><twig:block name="footer"><button class="confirm">OK</button></twig:block></twig:SylamentSupport:Modal>');

    expect($html)->toContain('sy-modal-footer')
        ->toContain('<button class="confirm">OK</button>');
});

it('applies a semantic width', function (): void {
    expect(renderModal('<twig:SylamentSupport:Modal id="m" width="lg" />'))
        ->toContain('class="sy sy-modal sy-modal-lg"');
});

it('renders no icon color classes for a gray icon', function (): void {
    expect(renderModal('<twig:SylamentSupport:Modal id="m" heading="H" icon="heroicons:x-mark" iconColor="gray" />'))
        ->toContain('<svg')
        ->not->toContain('sy-color');
});
