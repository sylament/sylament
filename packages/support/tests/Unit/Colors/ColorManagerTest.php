<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Colors;

use InvalidArgumentException;
use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Components\BadgeComponent;
use Sylament\Support\Colors\Components\ButtonComponent;
use Sylament\Support\Colors\Components\OutlinedButtonComponent;
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

it('defaults primary to amber and info to indigo', function (): void {
    $colors = colorManager()->getColors();

    expect($colors['primary'])->toBe(Color::Amber)
        ->and($colors['info'])->toBe(Color::Indigo);
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

it('accepts a palette carrying only some of the grid shades', function (): void {
    $manager = colorManager();
    $manager->register(['brand' => [50 => Color::Rose[50], 900 => Color::Rose[900]]]);

    expect(array_keys($manager->getColor('brand') ?? []))->toBe([50, 900]);
});

it('rejects a shade outside the grid the stylesheet defines rules for', function (): void {
    $manager = colorManager();
    $manager->register(['brand' => [50 => Color::Rose[50], 555 => Color::Rose[500]]]);

    expect(fn (): array => $manager->getColors())
        ->toThrow(InvalidArgumentException::class, 'The color palette [brand] carries the shade [555]');
});

it('rejects a shade key that would otherwise escape the style element', function (): void {
    // `sylamentStyles()` interpolates the shade key into a `<style>` element without escaping it,
    // which is safe only because no such key survives registration.
    $manager = colorManager();
    $manager->register(fn (): array => ['brand' => ['500;}</style><script>alert(1)</script>' => Color::Rose[500]]]);

    expect(fn (): array => $manager->getColors())->toThrow(InvalidArgumentException::class);
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

it('emits only the base classes for a registered but empty palette', function (): void {
    $manager = colorManager();
    $manager->register(['void' => []]);

    expect($manager->getComponentClasses(TextPolicy::class, 'void'))
        ->toBe(['sy-color', 'sy-color-void']);
});

it('emits the base classes plus the resolved shade class', function (): void {
    // Amber-600 on amber-50 is ~3.08:1, so the policy must resolve past it to 700.
    expect(colorManager()->getComponentClasses(TextPolicy::class, 'warning'))
        ->toBe(['sy-color', 'sy-color-warning', 'sy-text-color-700']);
});

it('accepts a policy handed over as an instance rather than a class-string', function (): void {
    // Every real policy holds the manager, which holds the container, so a cache key built by
    // serializing the policy walks straight into it.
    $manager = colorManager();

    expect($manager->getComponentClasses(new BadgeComponent($manager), 'warning'))
        ->toBe(['sy-color', 'sy-color-warning', 'sy-text-color-700', 'dark:sy-text-color-400']);
});

it('caches a policy instance apart from a sibling policy sharing its dependencies', function (): void {
    $manager = colorManager();

    expect($manager->getComponentClasses(new ButtonComponent($manager), 'warning'))
        ->not->toBe($manager->getComponentClasses(new OutlinedButtonComponent($manager), 'warning'));
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
