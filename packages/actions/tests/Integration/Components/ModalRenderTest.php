<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Components;

use Sylament\Actions\Action;
use Sylament\Support\Colors\Color;

/**
 * Covers the support-level native-`<dialog>` `<twig:SylamentSupport:Modal>`
 * primitive: closed-by-default chrome plus the `sylament--support--modal`
 * Stimulus wiring the actions layer drives in AC2.
 */
function renderModal(string $template, array $context = []): string
{
    return test()->getContainer()->get('twig')->createTemplate($template)->render($context);
}

it('renders a closed dialog with the modal controller wiring', function (): void {
    $html = renderModal('<twig:SylamentSupport:Modal id="delete-modal" heading="Delete?" />');

    expect($html)->toContain('<dialog')
        ->toContain('class="sy sy-modal sy-clickable"')
        ->toContain('sy-modal-window sy-modal-md')
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
        ->toContain('sy-modal-window sy-modal-lg');
});

it('renders no icon color classes for a gray icon', function (): void {
    expect(renderModal('<twig:SylamentSupport:Modal id="m" heading="H" icon="heroicons:x-mark" iconColor="gray" />'))
        ->toContain('<svg')
        ->not->toContain('sy-color');
});

it('carries an arbitrary icon palette as inline variables instead of a swap class', function (): void {
    $html = renderModal(
        '<twig:SylamentSupport:Modal id="m" heading="H" icon="heroicons:x-mark" :iconColor="palette" />',
        ['palette' => Color::Amber],
    );

    expect($html)->toContain('class="sy sy-icon sy-size-lg sy-color"')
        ->toContain('--sy-color-500: ' . Color::Amber[500]);
});

it('renders the close button by default inside the header', function (): void {
    $html = renderModal('<twig:SylamentSupport:Modal id="m" heading="H" />');

    expect($html)->toContain('sy-modal-close-btn')
        ->toContain('aria-label="Close"')
        ->toContain('tabindex="-1"')
        ->toContain('sylament--support--modal#dismiss')
        ->toContain('sy-modal-window-has-close-btn');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" heading="H" :closeButton="false" />'))
        ->not->toContain('sy-modal-close-btn');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" />'))
        ->not->toContain('sy-modal-close-btn');
});

it('moves initial focus to the window when the close button is the only keyboard dismissal', function (): void {
    $html = renderModal('<twig:SylamentSupport:Modal id="m" heading="H" :closeByEscaping="false" />');

    expect($html)->toMatch('/sy-modal-window[^>]*autofocus/')
        ->toContain('data-sylament--support--modal-close-by-escaping-value="false"');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" heading="H" :closeByEscaping="false" />'))
        ->not->toMatch('/sy-modal-close-btn[^>]*tabindex="-1"/');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" heading="H" :autofocus="false" />'))
        ->toMatch('/sy-modal-window[^>]*autofocus/');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" heading="H" />'))
        ->not->toMatch('/sy-modal-window[^>]*autofocus/');
});

it('wires the chrome interaction actions on the dialog', function (): void {
    $html = renderModal('<twig:SylamentSupport:Modal id="m" />');

    expect($html)->toContain('cancel-&gt;sylament--support--modal#handleCancel')
        ->toContain('pointerdown-&gt;sylament--support--modal#trackPointerDown')
        ->toContain('click-&gt;sylament--support--modal#closeOnClickAway')
        ->toContain('close-&gt;sylament--support--modal#notifyDismissed')
        ->toContain('sy-clickable');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" :closeByClickingAway="false" />'))
        ->toContain('data-sylament--support--modal-close-by-clicking-away-value="false"')
        ->not->toContain('sy-clickable');
});

it('renders slide-over and sticky variant classes on the root', function (): void {
    expect(renderModal('<twig:SylamentSupport:Modal id="m" :slideOver="true" />'))
        ->toContain('sy-modal-slide-over')
        ->toContain('sy-modal-slide-over-from-end');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" :slideOver="true" slideOverPosition="start" />'))
        ->toContain('sy-modal-slide-over-from-start');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" :stickyHeader="true" :stickyFooter="true" />'))
        ->toContain('sy-modal-has-sticky-header')
        ->toContain('sy-modal-has-sticky-footer');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" width="screen" />'))
        ->toContain('sy-modal-screen');
});

it('marks click-through modals and disables click-away for them', function (): void {
    $html = renderModal('<twig:SylamentSupport:Modal id="m" :clickThrough="true" />');

    expect($html)->toContain('sy-modal-click-through')
        ->toContain('data-sylament--support--modal-click-through-value="true"')
        ->toContain('data-sylament--support--modal-close-by-clicking-away-value="false"');
});

it('applies alignment classes to the window and header', function (): void {
    expect(renderModal('<twig:SylamentSupport:Modal id="m" />'))
        ->toContain('sy-align-start');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" alignment="center" />'))
        ->toContain('sy-align-center');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" alignment="funky" />'))
        ->toMatch('/sy-modal-window[^"]*funky/');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" heading="H" icon="heroicons:x-mark" />'))
        ->toContain('sy-vertical-align-center');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" heading="H" description="D" icon="heroicons:x-mark" />'))
        ->not->toContain('sy-vertical-align-center');
});

it('renders footer actions in the wrapper unless a footer slot wins', function (): void {
    $actions = [
        Action::make('confirm')->button(),
        Action::make('back')->button(),
    ];

    $html = renderModal('<twig:SylamentSupport:Modal id="m" :footerActions="actions" footerActionsAlignment="center" />', ['actions' => $actions]);

    expect($html)->toContain('sy-modal-footer-actions')
        ->toContain('sy-align-center')
        ->toContain('Confirm')
        ->toContain('Back');

    $slotWins = renderModal('<twig:SylamentSupport:Modal id="m" :footerActions="actions"><twig:block name="footer"><em>slot</em></twig:block></twig:SylamentSupport:Modal>', ['actions' => $actions]);

    expect($slotWins)->toContain('<em>slot</em>')
        ->not->toContain('sy-modal-footer-actions');
});

it('spreads extra overlay and window attributes onto their elements', function (): void {
    $html = renderModal('<twig:SylamentSupport:Modal id="m" :extraOverlayAttributes="{\'data-overlay\': \'o\', class: \'overlay-extra\'}" :extraWindowAttributes="{\'data-window\': \'w\', class: \'window-extra\'}" />');

    expect($html)->toMatch('/<dialog[^>]*data-overlay="o"/')
        ->toMatch('/<dialog[^>]*class="[^"]*overlay-extra/')
        ->toMatch('/sy-modal-window[^>]*data-window="w"/')
        ->toMatch('/class="[^"]*sy-modal-window[^"]*window-extra/');
});

it('mirrors the open state into the native open attribute', function (): void {
    expect(renderModal('<twig:SylamentSupport:Modal id="m" :open="true" />'))
        ->toMatch('/<dialog[^>]*\sopen[\s>]/')
        ->toContain('data-sylament--support--modal-open-value="true"');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" />'))
        ->not->toMatch('/<dialog[^>]*\sopen[\s>]/');
});

it('marks an overlaid modal so its dim is suppressed', function (): void {
    expect(renderModal('<twig:SylamentSupport:Modal id="m" :open="true" :overlaid="true" />'))
        ->toContain('sy-modal-overlaid');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" :open="true" />'))
        ->not->toContain('sy-modal-overlaid');
});

it('renders the window as a plain div when no submit handler is set', function (): void {
    $html = renderModal('<twig:SylamentSupport:Modal id="m" />');

    expect($html)->toMatch('/<div[^>]*\bsy-modal-window\b/')
        ->not->toContain('<form')
        ->not->toContain('data-live-action-param');
});

it('renders the window as a form carrying the live submit action when a submit handler is set', function (): void {
    $html = renderModal('<twig:SylamentSupport:Modal id="m" submitHandler="callMountedAction" />');

    expect($html)->toMatch('/<form[^>]*\bsy-modal-window\b/')
        ->toContain('data-action="submit->live#action:prevent"')
        ->toContain('data-live-action-param="callMountedAction"')
        ->toMatch('#</form>\s*</dialog>#')
        ->not->toMatch('/<div[^>]*\bsy-modal-window\b/');
});
