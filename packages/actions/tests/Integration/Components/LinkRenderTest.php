<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Components;

/**
 * Covers the support-level `<twig:SylamentSupport:Link>` primitive. It lives in
 * the actions suite because only a kernel that registers TwigComponentBundle
 * exercises the support Twig components, and actions is where its consumers are.
 */
function renderLink(string $template): string
{
    return test()->getContainer()->get('twig')->createTemplate($template)->render();
}

it('renders a primary medium anchor by default', function (): void {
    $html = renderLink('<twig:SylamentSupport:Link href="/settings">Settings</twig:SylamentSupport:Link>');

    expect($html)->toContain('<a')
        ->toContain('class="sy sy-link sy-size-md sy-color sy-color-primary sy-text-color-700 dark:sy-text-color-400"')
        ->toContain('href="/settings"')
        ->toContain('<span class="sy-link-label">Settings</span>')
        ->not->toContain('type=');
});

it('renders a button tag with a type and a form association', function (): void {
    $html = renderLink('<twig:SylamentSupport:Link tag="button" formId="my-form" type="submit">Save</twig:SylamentSupport:Link>');

    expect($html)->toContain('<button')
        ->toContain('type="submit"')
        ->toContain('form="my-form"');
});

it('renders a blank target', function (): void {
    expect(renderLink('<twig:SylamentSupport:Link href="/docs" target="_blank">Docs</twig:SylamentSupport:Link>'))
        ->toContain('target="_blank"');
});

it('disables a link button', function (): void {
    $html = renderLink('<twig:SylamentSupport:Link tag="button" :disabled="true">Delete</twig:SylamentSupport:Link>');

    expect($html)->toContain(' disabled')
        ->toContain('aria-disabled="true"')
        ->toContain('sy-disabled');
});

it('renders the gray color without color classes', function (): void {
    expect(renderLink('<twig:SylamentSupport:Link color="gray">Settings</twig:SylamentSupport:Link>'))
        ->toContain('class="sy sy-link sy-size-md"');
});

it('applies a semantic color', function (): void {
    expect(renderLink('<twig:SylamentSupport:Link color="danger">Delete</twig:SylamentSupport:Link>'))
        ->toContain('sy-color sy-color-danger sy-text-color-600 dark:sy-text-color-300');
});

it('emits an unknown size as a literal class', function (): void {
    expect(renderLink('<twig:SylamentSupport:Link size="custom-size">Settings</twig:SylamentSupport:Link>'))
        ->toContain('class="sy sy-link custom-size sy-color sy-color-primary sy-text-color-700 dark:sy-text-color-400"');
});

it('applies a font weight class', function (): void {
    expect(renderLink('<twig:SylamentSupport:Link weight="bold">Settings</twig:SylamentSupport:Link>'))
        ->toContain('sy-font-bold');
});

it('derives the icon size from a small link size', function (): void {
    expect(renderLink('<twig:SylamentSupport:Link size="sm" icon="heroicons:x-mark">Close</twig:SylamentSupport:Link>'))
        ->toContain('class="sy sy-icon sy-size-sm"');
});

it('leaves the icon size underived at medium, falling back to the icon default', function (): void {
    expect(renderLink('<twig:SylamentSupport:Link icon="heroicons:x-mark">Close</twig:SylamentSupport:Link>'))
        ->toContain('<svg')
        ->toContain('class="sy sy-icon sy-size-md"');
});

it('lets an explicit icon size beat the derived one', function (): void {
    expect(renderLink('<twig:SylamentSupport:Link size="sm" iconSize="lg" icon="heroicons:x-mark">Close</twig:SylamentSupport:Link>'))
        ->toContain('class="sy sy-icon sy-size-lg"');
});

it('renders the icon after the label when the position says so', function (): void {
    expect(renderLink('<twig:SylamentSupport:Link icon="heroicons:x-mark" iconPosition="after">Close</twig:SylamentSupport:Link>'))
        ->toMatch('#</span>\s*<svg#');
});

it('renders a sr-only label as an aria-label with no label span', function (): void {
    $html = renderLink('<twig:SylamentSupport:Link :labelSrOnly="true" icon="heroicons:x-mark">Delete</twig:SylamentSupport:Link>');

    expect($html)->toContain('aria-label="Delete"')
        ->not->toContain('sy-link-label');
});

it('forwards extra attributes onto the root element', function (): void {
    $html = renderLink('<twig:SylamentSupport:Link class="custom" data-owner="billing">Settings</twig:SylamentSupport:Link>');

    expect($html)->toContain('class="sy sy-link sy-size-md sy-color sy-color-primary sy-text-color-700 dark:sy-text-color-400 custom"')
        ->toContain('data-owner="billing"');
});
