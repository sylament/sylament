<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

/**
 * Covers the support-level `<twig:SylamentSupport:IconButton>` anonymous
 * component (the `<x-filament::icon-button>` equivalent). It lives here because
 * only the schemas test kernel registers TwigComponentBundle — the same home
 * the standalone support Icon coverage has in IconRenderTest.
 */
function renderIconButton(array $props): string
{
    return test()->getContainer()->get('twig')
        ->createTemplate("{{ component('SylamentSupport:IconButton', props) }}")
        ->render(['props' => $props]);
}

it('renders a primary medium button by default', function (): void {
    $html = renderIconButton(['icon' => 'heroicons:x-mark']);

    expect($html)->toContain('<button')
        ->toContain('class="sy sy-icon-btn sy-size-md sy-color sy-color-primary sy-text-color-600 hover:sy-text-color-700 dark:sy-text-color-500 dark:hover:sy-text-color-400"')
        ->toContain('type="button"')
        ->toContain('<svg')
        ->toContain('class="sy sy-icon sy-size-md"');
});

it('renders without an icon', function (): void {
    $html = renderIconButton([]);

    expect($html)->toContain('<button')
        ->not->toContain('<svg');
});

it('derives the icon size from the button size', function (string $size, string $iconSize): void {
    expect(renderIconButton(['icon' => 'heroicons:x-mark', 'size' => $size]))
        ->toContain("sy-size-{$size}")
        ->toContain("class=\"sy sy-icon sy-size-{$iconSize}\"");
})->with([
    'extra-small buttons shrink the icon' => ['xs', 'sm'],
    'large buttons grow the icon' => ['lg', 'lg'],
    'extra-large buttons cap the icon at large' => ['xl', 'lg'],
]);

it('prefers an explicit icon size over the derived one', function (): void {
    expect(renderIconButton(['icon' => 'heroicons:x-mark', 'size' => 'xs', 'iconSize' => 'lg']))
        ->toContain('class="sy sy-icon sy-size-lg"');
});

it('emits an unknown size string as a literal class', function (): void {
    expect(renderIconButton(['icon' => 'heroicons:x-mark', 'size' => 'huge']))
        ->toContain('class="sy sy-icon-btn huge sy-color sy-color-primary sy-text-color-600 hover:sy-text-color-700 dark:sy-text-color-500 dark:hover:sy-text-color-400"')
        ->not->toContain('sy-size-huge')
        ->toContain('class="sy sy-icon sy-size-md"');
});

it('renders a link with an href and a blank target', function (): void {
    $html = renderIconButton(['tag' => 'a', 'href' => '/settings', 'target' => '_blank']);

    expect($html)->toContain('<a')
        ->toContain('href="/settings"')
        ->toContain('target="_blank"')
        ->not->toContain('type=');
});

it('drops a non-blank link target', function (): void {
    expect(renderIconButton(['tag' => 'a', 'href' => '/settings', 'target' => 'other']))
        ->not->toContain('target=');
});

it('renders a link without an href when none is given', function (): void {
    expect(renderIconButton(['tag' => 'a']))->not->toContain('href=');
});

it('disables a button', function (): void {
    $html = renderIconButton(['disabled' => true]);

    expect($html)->toContain(' disabled')
        ->toContain('aria-disabled="true"')
        ->toContain('sy-disabled');
});

it('disables a link without the invalid disabled attribute', function (): void {
    $html = renderIconButton(['tag' => 'a', 'href' => '/settings', 'disabled' => true]);

    expect($html)->not->toContain(' disabled=')
        ->not->toMatch('/<a[^>]* disabled[ >]/')
        ->toContain('aria-disabled="true"')
        ->toContain('sy-disabled')
        ->toContain('href="/settings"');
});

it('exposes the label as the accessible name and title only', function (): void {
    $html = renderIconButton(['label' => 'Delete']);

    expect($html)->toContain('aria-label="Delete"')
        ->toContain('title="Delete"')
        ->not->toContain('>Delete<');
});

it('strips markup from the accessible name', function (): void {
    expect(renderIconButton(['label' => '<strong>Delete</strong> "now"']))
        ->toContain('aria-label="Delete &quot;now&quot;"');
});

it('renders the gray color without color classes', function (): void {
    expect(renderIconButton(['color' => 'gray']))
        ->toContain('class="sy sy-icon-btn sy-size-md"');
});

it('applies a semantic color', function (): void {
    expect(renderIconButton(['color' => 'danger']))
        ->toContain('sy-color sy-color-danger sy-text-color-600 hover:sy-text-color-500 dark:sy-text-color-400 dark:hover:sy-text-color-300');
});

it('associates with an external form and submits it', function (): void {
    $html = renderIconButton(['formId' => 'checkout', 'type' => 'submit']);

    expect($html)->toContain('form="checkout"')
        ->toContain('type="submit"');
});

it('forwards extra attributes onto the root element', function (): void {
    $html = renderIconButton(['class' => 'custom', 'data-owner' => 'billing']);

    expect($html)->toContain('class="sy sy-icon-btn sy-size-md sy-color sy-color-primary sy-text-color-600 hover:sy-text-color-700 dark:sy-text-color-500 dark:hover:sy-text-color-400 custom"')
        ->toContain('data-owner="billing"');
});
