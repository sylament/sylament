<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Components;

/**
 * Covers the support-level `<twig:SylamentSupport:Badge>` primitive. It lives in
 * the actions suite because only a kernel that registers TwigComponentBundle
 * exercises the support Twig components, and actions is where its consumers are.
 */
function renderBadge(string $template): string
{
    return test()->getContainer()->get('twig')->createTemplate($template)->render();
}

it('renders a primary medium badge on a span by default', function (): void {
    $html = renderBadge('<twig:SylamentSupport:Badge>New</twig:SylamentSupport:Badge>');

    expect($html)->toContain('<span')
        ->toContain('class="sy sy-badge sy-size-md sy-color sy-color-primary"')
        ->toContain('<span class="sy-badge-label-ctn"><span class="sy-badge-label">New</span></span>')
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
        ->toContain('sy-color sy-color-danger');
});

it('emits an unknown size as a literal class', function (): void {
    expect(renderBadge('<twig:SylamentSupport:Badge size="custom-size">New</twig:SylamentSupport:Badge>'))
        ->toContain('class="sy sy-badge custom-size sy-color sy-color-primary"');
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

    expect($html)->toContain('class="sy sy-badge sy-size-md sy-color sy-color-primary custom"')
        ->toContain('data-owner="billing"');
});
