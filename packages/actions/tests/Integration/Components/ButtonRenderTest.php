<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Components;

/**
 * Covers the support-level `<twig:SylamentSupport:Button>` text-button primitive.
 * It lives in the actions suite because only a kernel that registers
 * TwigComponentBundle exercises the support Twig components — the actions kernel
 * is the nearest such home for the button/modal chrome (per CLAUDE.md).
 */
function renderButton(string $template): string
{
    return test()->getContainer()->get('twig')->createTemplate($template)->render();
}

it('renders a primary medium button by default', function (): void {
    $html = renderButton('<twig:SylamentSupport:Button>Save</twig:SylamentSupport:Button>');

    expect($html)->toContain('<button')
        ->toContain('class="sy sy-btn sy-size-md sy-color sy-color-primary"')
        ->toContain('type="button"')
        ->toContain('Save');
});

it('renders a link with an href and a blank target', function (): void {
    $html = renderButton('<twig:SylamentSupport:Button tag="a" href="/settings" target="_blank">Settings</twig:SylamentSupport:Button>');

    expect($html)->toContain('<a')
        ->toContain('href="/settings"')
        ->toContain('target="_blank"')
        ->not->toContain('type=');
});

it('disables a button', function (): void {
    $html = renderButton('<twig:SylamentSupport:Button :disabled="true">Delete</twig:SylamentSupport:Button>');

    expect($html)->toContain(' disabled')
        ->toContain('aria-disabled="true"')
        ->toContain('sy-disabled');
});

it('renders the gray color without color classes', function (): void {
    expect(renderButton('<twig:SylamentSupport:Button color="gray">Save</twig:SylamentSupport:Button>'))
        ->toContain('class="sy sy-btn sy-size-md"');
});

it('applies a semantic color', function (): void {
    expect(renderButton('<twig:SylamentSupport:Button color="danger">Delete</twig:SylamentSupport:Button>'))
        ->toContain('sy-color sy-color-danger');
});

it('renders a leading icon', function (): void {
    expect(renderButton('<twig:SylamentSupport:Button icon="heroicons:x-mark">Close</twig:SylamentSupport:Button>'))
        ->toContain('<svg');
});

it('forwards extra attributes onto the root element', function (): void {
    $html = renderButton('<twig:SylamentSupport:Button class="custom" data-owner="billing">Save</twig:SylamentSupport:Button>');

    expect($html)->toContain('class="sy sy-btn sy-size-md sy-color sy-color-primary custom"')
        ->toContain('data-owner="billing"');
});
