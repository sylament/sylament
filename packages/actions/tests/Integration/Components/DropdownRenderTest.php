<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Components;

/**
 * Covers the support-level `<twig:SylamentSupport:Dropdown>` primitive. It lives in
 * the actions suite because only a kernel registering TwigComponentBundle exercises
 * the support Twig components — same placement as the Button/Modal render tests.
 */
function renderDropdown(string $template): string
{
    return test()->getContainer()->get('twig')->createTemplate($template)->render();
}

it('renders a positioned dropdown shell with trigger and hidden panel', function (): void {
    $html = renderDropdown(<<<'TWIG'
        <twig:SylamentSupport:Dropdown>
            <twig:block name="trigger"><button type="button">Open</button></twig:block>
            <span>Panel content</span>
        </twig:SylamentSupport:Dropdown>
        TWIG);

    expect($html)->toContain('sy sy-dropdown')
        ->toContain('data-controller="sylament--support--dropdown"')
        ->toContain('data-sylament--support--dropdown-placement-value="bottom-start"')
        ->toContain('data-sylament--support--dropdown-offset-value="8"')
        ->toContain('data-sylament--support--dropdown-flip-value="true"')
        ->toContain('sy-dropdown-trigger')
        ->toContain('data-action="click->sylament--support--dropdown#toggle"')
        ->toContain('data-sylament--support--dropdown-target="trigger"')
        ->toContain('<button type="button">Open</button>')
        ->toContain('sy-dropdown-panel')
        ->toContain('data-sylament--support--dropdown-target="panel"')
        ->toContain(' hidden')
        ->toContain('Panel content');
});

it('applies placement, width, and max-height configuration', function (): void {
    $html = renderDropdown(<<<'TWIG'
        <twig:SylamentSupport:Dropdown placement="top-end" width="16rem" maxHeight="20rem" :flip="false">
            <twig:block name="trigger"><button type="button">Open</button></twig:block>
            <span>Panel content</span>
        </twig:SylamentSupport:Dropdown>
        TWIG);

    expect($html)->toContain('data-sylament--support--dropdown-placement-value="top-end"')
        ->toContain('data-sylament--support--dropdown-flip-value="false"')
        ->toContain('--sy-dropdown-width: 16rem;')
        ->toContain('--sy-dropdown-max-height: 20rem;');
});
