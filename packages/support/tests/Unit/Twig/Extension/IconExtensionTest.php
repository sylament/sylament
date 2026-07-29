<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Twig\Extension;

use Sylament\Support\Icons\IconManager;
use Sylament\Support\Tests\Fixtures\Icons\IconEnumFixture;
use Sylament\Support\Tests\Fixtures\Icons\IconRendererFixture;
use Sylament\Support\Tests\Fixtures\Icons\ScalableIconFixture;
use Sylament\Support\Twig\Extension\IconExtension;
use Twig\Markup;

/**
 * @return array{IconExtension, IconRendererFixture, IconManager}
 */
function iconExtension(): array
{
    $renderer = new IconRendererFixture();
    $icons = new IconManager($renderer);

    return [new IconExtension($icons), $renderer, $icons];
}

it('registers the generateIconHtml function', function (): void {
    [$extension] = iconExtension();

    $functions = $extension->getFunctions();

    expect($functions)->toHaveCount(1)
        ->and($functions[0]->getName())->toBe('generateIconHtml');
});

it('returns null for a blank icon', function (): void {
    [$extension] = iconExtension();

    expect($extension->generateIconHtml())->toBeNull()
        ->and($extension->generateIconHtml('   '))->toBeNull()
        ->and($extension->generateIconHtml(new Markup('  ', 'UTF-8')))->toBeNull();
});

it('renders an icon name through the renderer as Markup', function (): void {
    [$extension] = iconExtension();

    $html = $extension->generateIconHtml('heroicons:x-mark');

    expect($html)->toBeInstanceOf(Markup::class)
        ->and((string) $html)->toBe('<svg data-icon="heroicons:x-mark"></svg>');
});

it('applies sy-icon and the medium size class by default', function (): void {
    [$extension, $renderer] = iconExtension();

    $extension->generateIconHtml('heroicons:x-mark');

    expect($renderer->lastAttributes)->toBe(['class' => 'sy sy-icon sy-size-md']);
});

it('applies an explicit size class', function (): void {
    [$extension, $renderer] = iconExtension();

    $extension->generateIconHtml('heroicons:x-mark', size: '2xl');

    expect($renderer->lastAttributes)->toBe(['class' => 'sy sy-icon sy-size-2xl']);
});

it('normalizes a known size string to its enum case', function (): void {
    [$extension, $renderer] = iconExtension();

    $extension->generateIconHtml('heroicons:x-mark', size: 'lg');

    expect($renderer->lastAttributes)->toBe(['class' => 'sy sy-icon sy-size-lg']);
});

it('degrades an unknown size string to the medium default', function (): void {
    [$extension, $renderer] = iconExtension();

    $extension->generateIconHtml('heroicons:x-mark', size: 'huge');

    expect($renderer->lastAttributes)->toBe(['class' => 'sy sy-icon sy-size-md']);
});

it('lets a resolvable alias win over the given icon', function (): void {
    [$extension, $renderer, $icons] = iconExtension();
    $icons->register(['section.collapse-button' => 'heroicons:chevron-up']);

    $extension->generateIconHtml('heroicons:x-mark', 'section.collapse-button');

    expect($renderer->lastName)->toBe('heroicons:chevron-up');
});

it('falls back to the given icon for an unresolvable alias', function (): void {
    [$extension, $renderer] = iconExtension();

    $extension->generateIconHtml('heroicons:x-mark', 'unknown.alias');

    expect($renderer->lastName)->toBe('heroicons:x-mark');
});

it('resolves the first matching alias from a list', function (): void {
    [$extension, $renderer, $icons] = iconExtension();
    $icons->register(['second.alias' => 'heroicons:user']);

    $extension->generateIconHtml('heroicons:x-mark', ['first.alias', 'second.alias']);

    expect($renderer->lastName)->toBe('heroicons:user');
});

it('appends caller attributes after the defaults and drops null and false values', function (): void {
    [$extension, $renderer] = iconExtension();

    $extension->generateIconHtml('heroicons:x-mark', attributes: [
        'class' => 'custom',
        'data-x' => '1',
        'data-none' => null,
        'data-off' => false,
        'data-on' => true,
    ]);

    expect($renderer->lastAttributes)->toBe([
        'class' => 'sy sy-icon sy-size-md custom',
        'data-x' => '1',
        'data-on' => 'true',
    ]);
});

it('wraps a Markup icon raw inside a span', function (): void {
    [$extension] = iconExtension();

    $html = $extension->generateIconHtml(new Markup('<svg data-custom></svg>', 'UTF-8'));

    expect((string) $html)->toBe('<span class="sy sy-icon sy-size-md"><svg data-custom></svg></span>');
});

it('renders a path icon as a decorative image', function (): void {
    [$extension] = iconExtension();

    $html = $extension->generateIconHtml('images/lo"go.svg');

    expect((string) $html)->toBe('<img src="images/lo&quot;go.svg" alt="" class="sy sy-icon sy-size-md" />');
});

it('keeps a named path icon non-decorative', function (): void {
    [$extension] = iconExtension();

    $html = $extension->generateIconHtml('images/logo.svg', attributes: ['aria-label' => 'Logo']);

    expect((string) $html)->toContain('aria-label="Logo"')
        ->not->toContain('alt=');
});

it('scales a ScalableIcon with the effective size', function (): void {
    [$extension, $renderer] = iconExtension();

    $extension->generateIconHtml(ScalableIconFixture::Star);

    expect($renderer->lastName)->toBe('fixture:star-md');

    $extension->generateIconHtml(ScalableIconFixture::Star, size: 'sm');

    expect($renderer->lastName)->toBe('fixture:star-sm');
});

it('renders a plain backed enum by its value', function (): void {
    [$extension, $renderer] = iconExtension();

    $extension->generateIconHtml(IconEnumFixture::Star);

    expect($renderer->lastName)->toBe('heroicons:star');
});
