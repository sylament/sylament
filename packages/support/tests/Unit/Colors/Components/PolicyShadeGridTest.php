<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Colors\Components;

use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Components\BadgeComponent;
use Sylament\Support\Colors\Components\ButtonComponent;
use Sylament\Support\Colors\Components\DropdownComponent\ItemComponent;
use Sylament\Support\Colors\Components\IconButtonComponent;
use Sylament\Support\Colors\Components\LinkComponent;
use Sylament\Support\Colors\Components\OutlinedButtonComponent;
use Sylament\Support\Colors\Components\ToggleComponent;

use function Sylament\Support\Tests\colorManagerWith;

/**
 * A shade a policy resolves becomes a class name, and a class name with no rule fails in
 * silence: the slot variable is never set, the declaration reading it is invalid at
 * computed-value time, and the element renders uncoloured with nothing in the console. This
 * ties the shades the policies can emit to the shades `utilities.css` actually defines.
 */
dataset('adversarial palettes', function (): iterable {
    $shades = array_keys(ColorManager::DEFAULT_COLORS['gray']);

    yield 'uniformly dark' => [array_fill_keys($shades, 'oklch(0.15 0.05 260)')];

    yield 'pale but for the darkest shade' => [
        array_replace(array_fill_keys($shades, 'oklch(0.99 0.01 90)'), [950 => 'oklch(0.2 0.05 90)']),
    ];

    yield 'dark but for the lightest shade' => [
        array_replace(array_fill_keys($shades, 'oklch(0.15 0.05 260)'), [50 => 'oklch(0.99 0 0)']),
    ];

    foreach (ColorManager::DEFAULT_COLORS as $name => $palette) {
        yield $name => [$palette];
    }
});

it('emits only shade classes that utilities.css defines', function (array $palette): void {
    $css = file_get_contents(__DIR__ . '/../../../../assets/src/styles/utilities.css');

    expect($css)->toBeString();

    $policies = [
        BadgeComponent::class,
        ButtonComponent::class,
        OutlinedButtonComponent::class,
        IconButtonComponent::class,
        LinkComponent::class,
        ToggleComponent::class,
        ItemComponent::class,
    ];

    foreach ($policies as $class) {
        $colors = colorManagerWith('brand', $palette);

        $shadeClasses = array_filter(
            $colors->getComponentClasses(new $class($colors), 'brand'),
            fn (string $class): bool => (bool) preg_match('/sy-(?:bg|text)-color-/', $class),
        );

        expect($shadeClasses)->not->toBeEmpty();

        foreach ($shadeClasses as $shadeClass) {
            expect($css)->toContain('.' . str_replace(':', '\\:', $shadeClass) . ' {');
        }
    }
})->with('adversarial palettes');
