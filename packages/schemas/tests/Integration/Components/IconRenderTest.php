<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Support\Icons\IconManager;

/**
 * Covers the support-level `<twig:SylamentSupport:Icon>` anonymous component
 * (the `<x-filament::icon>` equivalent). It lives here because only the schemas
 * test kernel registers TwigComponentBundle — the same home the standalone
 * support Section coverage has in SectionRenderTest.
 */
function renderIcon(array $props): string
{
    return test()->getContainer()->get('twig')
        ->createTemplate("{{ component('SylamentSupport:Icon', props) }}")
        ->render(['props' => $props]);
}

it('renders an icon by name at the medium default size', function (): void {
    $html = renderIcon(['icon' => 'heroicons:x-mark']);

    expect($html)->toContain('<svg')
        ->toContain('class="sy-icon sy-size-md"')
        ->toContain('aria-hidden="true"');
});

it('renders nothing without an icon', function (): void {
    expect(mb_trim(renderIcon([])))->toBe('');
});

it('applies an explicit size', function (): void {
    expect(renderIcon(['icon' => 'heroicons:x-mark', 'size' => 'lg']))
        ->toContain('class="sy-icon sy-size-lg"');
});

it('forwards extra attributes into the icon markup', function (): void {
    $html = renderIcon(['icon' => 'heroicons:x-mark', 'class' => 'custom', 'data-owner' => 'billing']);

    expect($html)->toContain('class="sy-icon sy-size-md custom"')
        ->toContain('data-owner="billing"');
});

it('resolves a registered alias', function (): void {
    $this::getContainer();
    IconManager::getInstance()->register(['test.alias' => 'heroicons:x-mark']);

    expect(renderIcon(['alias' => 'test.alias']))->toContain('<svg');
});
