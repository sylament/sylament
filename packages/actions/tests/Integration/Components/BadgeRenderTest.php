<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Components;

use Sylament\Support\Colors\Color;

/**
 * Covers the support-level `<twig:SylamentSupport:Badge>` primitive. It lives in
 * the actions suite because only a kernel that registers TwigComponentBundle
 * exercises the support Twig components, and actions is where its consumers are.
 *
 * @param array<string, mixed> $context
 */
function renderBadge(string $template, array $context = []): string
{
    return test()->getContainer()->get('twig')->createTemplate($template)->render($context);
}

it('renders a primary medium badge on a span by default', function (): void {
    $html = renderBadge('<twig:SylamentSupport:Badge>New</twig:SylamentSupport:Badge>');

    expect($html)->toContain('<span')
        ->toContain('class="sy sy-badge sy-size-md sy-color sy-color-primary sy-text-color-700 dark:sy-text-color-400"')
        ->toContain('<span class="sy-badge-label-ctn"><span class="sy-badge-label">New</span></span>')
        ->not->toContain('style=')
        ->not->toContain('type=');
});

it('renders a button tag with a type and a form association', function (): void {
    $html = renderBadge('<twig:SylamentSupport:Badge tag="button" formId="my-form" type="submit">Save</twig:SylamentSupport:Badge>');

    expect($html)->toContain('<button')
        ->toContain('type="submit"')
        ->toContain('form="my-form"');
});

it('renders an anchor with an href and a blank target', function (): void {
    $html = renderBadge('<twig:SylamentSupport:Badge tag="a" href="/inbox" target="_blank">Inbox</twig:SylamentSupport:Badge>');

    expect($html)->toContain('<a')
        ->toContain('href="/inbox"')
        ->toContain('target="_blank"')
        ->not->toContain('type=');
});

it('disables a badge button', function (): void {
    $html = renderBadge('<twig:SylamentSupport:Badge tag="button" :disabled="true">Delete</twig:SylamentSupport:Badge>');

    expect($html)->toContain(' disabled')
        ->toContain('aria-disabled="true"')
        ->toContain('sy-disabled');
});

it('renders the gray color without color classes', function (): void {
    expect(renderBadge('<twig:SylamentSupport:Badge color="gray">New</twig:SylamentSupport:Badge>'))
        ->toContain('class="sy sy-badge sy-size-md"');
});

it('applies a semantic color', function (): void {
    expect(renderBadge('<twig:SylamentSupport:Badge color="danger">Overdue</twig:SylamentSupport:Badge>'))
        ->toContain('sy-color sy-color-danger sy-text-color-700 dark:sy-text-color-200');
});

it('emits an unknown size as a literal class', function (): void {
    expect(renderBadge('<twig:SylamentSupport:Badge size="custom-size">New</twig:SylamentSupport:Badge>'))
        ->toContain('class="sy sy-badge custom-size sy-color sy-color-primary sy-text-color-700 dark:sy-text-color-400"');
});

it('defaults the icon size to sm', function (): void {
    expect(renderBadge('<twig:SylamentSupport:Badge icon="heroicons:x-mark">Close</twig:SylamentSupport:Badge>'))
        ->toContain('<svg')
        ->toContain('class="sy sy-icon sy-size-sm"');
});

it('renders the icon after the label when the position says so', function (): void {
    $html = renderBadge('<twig:SylamentSupport:Badge icon="heroicons:x-mark" iconPosition="after">Close</twig:SylamentSupport:Badge>');

    expect($html)->toMatch('#</span></span>\s*<svg#');
});

it('renders a sr-only label as an aria-label with no label spans', function (): void {
    $html = renderBadge('<twig:SylamentSupport:Badge :labelSrOnly="true" icon="heroicons:x-mark">Delete</twig:SylamentSupport:Badge>');

    expect($html)->toContain('aria-label="Delete"')
        ->not->toContain('sy-badge-label');
});

it('forwards extra attributes onto the root element', function (): void {
    $html = renderBadge('<twig:SylamentSupport:Badge class="custom" data-owner="billing">New</twig:SylamentSupport:Badge>');

    expect($html)->toContain('class="sy sy-badge sy-size-md sy-color sy-color-primary sy-text-color-700 dark:sy-text-color-400 custom"')
        ->toContain('data-owner="billing"');
});

it('carries an arbitrary palette as inline variables instead of a swap class', function (): void {
    $html = renderBadge(
        '<twig:SylamentSupport:Badge :color="palette">Overdue</twig:SylamentSupport:Badge>',
        ['palette' => Color::Amber],
    );

    expect($html)->toContain('class="sy sy-badge sy-size-md sy-color"')
        ->toContain('--sy-color-50: ' . Color::Amber[50])
        ->toContain('--sy-color-950: ' . Color::Amber[950])
        ->toContain('--sy-text: var(--sy-color-700); --sy-dark-text: var(--sy-color-400)');
});

it('appends a caller style to the palette instead of letting it replace the whole attribute', function (): void {
    $html = renderBadge(
        '<twig:SylamentSupport:Badge :color="palette" style="margin: 0">Overdue</twig:SylamentSupport:Badge>',
        ['palette' => Color::Amber],
    );

    expect($html)->toContain('--sy-color-50: ' . Color::Amber[50])
        ->toContain('--sy-dark-text: var(--sy-color-400); margin: 0"');
});

it('keeps a caller style on a named colour that emits no palette of its own', function (): void {
    expect(renderBadge('<twig:SylamentSupport:Badge color="danger" style="margin: 0">Overdue</twig:SylamentSupport:Badge>'))
        ->toContain('style="margin: 0"');
});
