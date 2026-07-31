<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Components;

/**
 * Covers the support-level `<twig:SylamentSupport:DropdownItem>` primitive. It
 * lives in the actions suite because only a kernel that registers
 * TwigComponentBundle exercises the support Twig components, and actions is
 * where its two consumers are.
 */
function renderDropdownItem(string $template): string
{
    return test()->getContainer()->get('twig')->createTemplate($template)->render();
}

it('renders a gray button row by default', function (): void {
    $html = renderDropdownItem('<twig:SylamentSupport:DropdownItem>Delete</twig:SylamentSupport:DropdownItem>');

    expect($html)->toContain('<button')
        ->toContain('class="sy sy-dropdown-item"')
        ->toContain('type="button"')
        ->toContain('<span class="sy-dropdown-item-label">Delete</span>');
});

it('renders an anchor with an href and a blank target', function (): void {
    $html = renderDropdownItem('<twig:SylamentSupport:DropdownItem tag="a" href="/users/1/edit" target="_blank">Edit</twig:SylamentSupport:DropdownItem>');

    expect($html)->toContain('<a')
        ->toContain('href="/users/1/edit"')
        ->toContain('target="_blank"')
        ->not->toContain('type=');
});

it('renders a submit type and a form association', function (): void {
    $html = renderDropdownItem('<twig:SylamentSupport:DropdownItem type="submit" formId="my-form">Save</twig:SylamentSupport:DropdownItem>');

    expect($html)->toContain('type="submit"')
        ->toContain('form="my-form"');
});

it('disables an item', function (): void {
    $html = renderDropdownItem('<twig:SylamentSupport:DropdownItem :disabled="true">Delete</twig:SylamentSupport:DropdownItem>');

    expect($html)->toContain(' disabled')
        ->toContain('aria-disabled="true"')
        ->toContain('sy-disabled');
});

it('applies a semantic color', function (): void {
    expect(renderDropdownItem('<twig:SylamentSupport:DropdownItem color="danger">Delete</twig:SylamentSupport:DropdownItem>'))
        ->toContain('sy-color sy-color-danger sy-text-color-600 hover:sy-text-color-700 dark:sy-text-color-400 dark:hover:sy-text-color-400');
});

it('renders an icon before the label', function (): void {
    expect(renderDropdownItem('<twig:SylamentSupport:DropdownItem icon="heroicons:x-mark">Delete</twig:SylamentSupport:DropdownItem>'))
        ->toMatch('#<svg.*<span class="sy-dropdown-item-label">#s');
});

it('forwards extra attributes onto the root element', function (): void {
    $html = renderDropdownItem('<twig:SylamentSupport:DropdownItem class="custom" data-owner="billing">Delete</twig:SylamentSupport:DropdownItem>');

    expect($html)->toContain('class="sy sy-dropdown-item custom"')
        ->toContain('data-owner="billing"');
});
