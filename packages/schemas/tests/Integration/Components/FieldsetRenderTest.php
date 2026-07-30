<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

/**
 * Covers the support-level `<twig:SylamentSupport:Fieldset>` anonymous component
 * (the `<x-filament::fieldset>` equivalent). It lives here because only the schemas
 * test kernel registers TwigComponentBundle — the same home the standalone support
 * Icon and IconButton coverage has. The schemas-level `Fieldset` view component is
 * covered separately in FieldsetComponentRenderTest.
 *
 * @param array<string, mixed> $props
 */
function renderSupportFieldset(array $props): string
{
    return test()->getContainer()->get('twig')
        ->createTemplate("{{ component('SylamentSupport:Fieldset', props) }}")
        ->render(['props' => $props]);
}

it('renders a contained fieldset with a legend', function (): void {
    $html = renderSupportFieldset(['label' => 'Address', 'content' => 'body']);

    expect($html)->toContain('<fieldset')
        ->toContain('class="sy sy-fieldset"')
        ->toContain('<legend>Address</legend>')
        ->toContain('body');
});

it('omits the legend without a label', function (): void {
    $html = renderSupportFieldset(['content' => 'body']);

    expect($html)->not->toContain('<legend');
});

it('marks a hidden label without dropping it from the DOM', function (): void {
    $html = renderSupportFieldset(['label' => 'Address', 'labelHidden' => true]);

    expect($html)->toContain('sy-fieldset-label-hidden')
        ->toContain('<legend>Address</legend>');
});

it('flags an uncontained fieldset', function (): void {
    $html = renderSupportFieldset(['label' => 'Address', 'contained' => false]);

    expect($html)->toContain('sy-fieldset-not-contained');
});

it('renders the required mark only when required', function (): void {
    expect(renderSupportFieldset(['label' => 'Address', 'required' => true]))
        ->toContain('<sup class="sy-fieldset-label-required-mark">*</sup>')
        ->and(renderSupportFieldset(['label' => 'Address']))
        ->not->toContain('sy-fieldset-label-required-mark');
});

it('appends a caller class to its own', function (): void {
    $html = renderSupportFieldset(['label' => 'Address', 'class' => 'sy-sc-fieldset']);

    expect($html)->toContain('class="sy sy-fieldset sy-sc-fieldset"');
});
