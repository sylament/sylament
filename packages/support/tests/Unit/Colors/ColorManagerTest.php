<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Colors;

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Tests\Fixtures\Colors\EmptyMapPolicy;
use Sylament\Support\Tests\Fixtures\Colors\GrayDefaultPolicy;
use Sylament\Support\Tests\Fixtures\Colors\TextPolicy;
use Symfony\Component\DependencyInjection\ContainerBuilder;

function colorManager(): ColorManager
{
    $container = new ContainerBuilder();
    $container->register(TextPolicy::class, TextPolicy::class)->setPublic(true);
    $container->register(EmptyMapPolicy::class, EmptyMapPolicy::class)->setPublic(true);
    $container->register(GrayDefaultPolicy::class, GrayDefaultPolicy::class)->setPublic(true);
    $container->compile();

    return new ColorManager($container);
}

it('ships the six default palettes', function (): void {
    expect(array_keys(colorManager()->getColors()))
        ->toBe(['danger', 'gray', 'info', 'primary', 'success', 'warning']);
});

it('defaults primary to blue and info to sky', function (): void {
    $colors = colorManager()->getColors();

    expect($colors['primary'])->toBe(Color::Blue)
        ->and($colors['info'])->toBe(Color::Sky);
});

it('registers an additional palette', function (): void {
    $manager = colorManager();
    $manager->register(['brand' => Color::Rose]);

    expect($manager->getColor('brand'))->toBe(Color::Rose);
});

it('overrides a default palette', function (): void {
    $manager = colorManager();
    $manager->register(['primary' => Color::Rose]);

    expect($manager->getColor('primary'))->toBe(Color::Rose);
});

it('generates a palette from a single colour string', function (): void {
    $manager = colorManager();
    $manager->register(['brand' => '#7c3aed']);

    expect($manager->getColor('brand'))->toHaveCount(11);
});

it('returns null for an unregistered palette', function (): void {
    expect(colorManager()->getColor('nope'))->toBeNull();
});

it('emits no classes for a blank colour', function (): void {
    expect(colorManager()->getComponentClasses(TextPolicy::class, null))->toBe([]);
});

it('emits no classes for gray on a component defaulting to gray', function (): void {
    expect(colorManager()->getComponentClasses(GrayDefaultPolicy::class, 'gray'))->toBe([]);
});

it('emits only the base classes for an unregistered colour', function (): void {
    expect(colorManager()->getComponentClasses(TextPolicy::class, 'nope'))
        ->toBe(['sy-color', 'sy-color-nope']);
});

it('emits the base classes plus the resolved shade class', function (): void {
    // Amber-600 on amber-50 is ~3.08:1, so the policy must resolve past it to 700.
    expect(colorManager()->getComponentClasses(TextPolicy::class, 'warning'))
        ->toBe(['sy-color', 'sy-color-warning', 'sy-text-color-700']);
});

it('emits only the base classes when the map is empty', function (): void {
    expect(colorManager()->getComponentClasses(EmptyMapPolicy::class, 'warning'))
        ->toBe(['sy-color', 'sy-color-warning']);
});

it('strips characters that could escape a style attribute', function (): void {
    $styles = colorManager()->getComponentCustomStyles(EmptyMapPolicy::class, [
        50 => 'oklch(0.9 0.1 90)',
        900 => '"; background: url(evil)',
    ]);

    expect(implode('; ', $styles))->not->toContain('url(evil)');
});

it('emits inline variables for a custom palette', function (): void {
    $styles = colorManager()->getComponentCustomStyles(TextPolicy::class, Color::Amber);

    expect($styles)->toContain('--sy-color-50: ' . Color::Amber[50])
        ->and(implode('; ', $styles))->toContain('--sy-text: var(--sy-color-');
});
