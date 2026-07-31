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
        ->toContain('class="sy sy-btn sy-size-md sy-color sy-color-primary sy-bg-color-400 hover:sy-bg-color-300 dark:sy-bg-color-600 dark:hover:sy-bg-color-500 sy-text-color-900 hover:sy-text-color-800 dark:sy-text-color-950 dark:hover:sy-text-color-950"')
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
        ->toContain('sy-color sy-color-danger sy-bg-color-600 hover:sy-bg-color-500 dark:sy-bg-color-600 dark:hover:sy-bg-color-500 sy-text-color-0 hover:sy-text-color-0 dark:sy-text-color-0 dark:hover:sy-text-color-0');
});

it('resolves an outlined button against the page surface instead of its own background', function (): void {
    expect(renderButton('<twig:SylamentSupport:Button color="danger" :outlined="true">Delete</twig:SylamentSupport:Button>'))
        ->toContain('class="sy sy-btn sy-outlined sy-size-md sy-color sy-color-danger sy-text-color-600 dark:sy-text-color-300"');
});

it('renders a leading icon', function (): void {
    expect(renderButton('<twig:SylamentSupport:Button icon="heroicons:x-mark">Close</twig:SylamentSupport:Button>'))
        ->toContain('<svg');
});

it('forwards extra attributes onto the root element', function (): void {
    $html = renderButton('<twig:SylamentSupport:Button class="custom" data-owner="billing">Save</twig:SylamentSupport:Button>');

    expect($html)->toContain('class="sy sy-btn sy-size-md sy-color sy-color-primary sy-bg-color-400 hover:sy-bg-color-300 dark:sy-bg-color-600 dark:hover:sy-bg-color-500 sy-text-color-900 hover:sy-text-color-800 dark:sy-text-color-950 dark:hover:sy-text-color-950 custom"')
        ->toContain('data-owner="billing"');
});
