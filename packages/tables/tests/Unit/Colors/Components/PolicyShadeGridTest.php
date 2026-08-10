<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Unit\Colors\Components;

use Sylament\Support\Colors\ColorManager;
use Sylament\Tables\Colors\Components\TextColumnComponent\IconComponent;
use Sylament\Tables\Colors\Components\TextColumnComponent\ItemComponent;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use UnexpectedValueException;

use function dirname;

/**
 * `ItemComponent` is the one tables policy that resolves shades, and the two stylesheets it has to
 * agree with ship from two packages: support's `utilities.css` defines the shade classes
 * `getComponentClasses()` names, and tables' own `components/text-column.css` reads the slot
 * variables those classes set. Neither support's nor schemas' `PolicyShadeGridTest` can cover it —
 * a test one package down cannot see a tables class — and both links fail in silence: an undefined
 * class sets no variable, a variable nobody sets makes the declaration reading it invalid at
 * computed-value time, and `.sy-ta-text-item` renders in the inherited colour with nothing in the
 * console.
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

function stylesheet(string $path): string
{
    $css = file_get_contents($path);

    if ($css === false) {
        throw new UnexpectedValueException("Unable to read {$path}.");
    }

    return $css;
}

function supportStylesheet(string $name): string
{
    return stylesheet(dirname(__DIR__, 5) . "/support/assets/src/styles/{$name}");
}

function textColumnStylesheet(): string
{
    return stylesheet(dirname(__DIR__, 4) . '/assets/src/styles/components/text-column.css');
}

/**
 * @param array<int, string> $palette
 * @return array{ColorManager, list<string>} the manager holding the registered palette, and the
 *                                           classes it emits for the given policy
 */
function emittedClasses(string $policy, array $palette): array
{
    $colors = new ColorManager(new ContainerBuilder());
    $colors->register(['brand' => $palette]);

    $component = $policy === ItemComponent::class ? new ItemComponent($colors) : new IconComponent();

    return [$colors, $colors->getComponentClasses($component, 'brand')];
}

/**
 * @param array<int, string> $palette
 * @return list<string> the emitted classes with the two carriers `getComponentClasses()` always
 *                      prepends removed, leaving only what the colour map resolved
 */
function itemShadeClasses(array $palette): array
{
    [, $classes] = emittedClasses(ItemComponent::class, $palette);

    return array_values(array_diff($classes, ['sy-color', 'sy-color-brand']));
}

/**
 * @return list<string> the `--sy-` slot variables the coloured-item rule reads
 */
function itemSlotVariablesRead(): array
{
    if (preg_match('/&\.sy-color\s*\{(?<declarations>[^}]*)\}/', textColumnStylesheet(), $rule) !== 1) {
        throw new UnexpectedValueException('No `&.sy-color` rule in components/text-column.css — the coloured-item branch has moved or gone.');
    }

    preg_match_all('/--sy-[a-z-]+/', $rule['declarations'], $variables);

    return $variables[0];
}

/**
 * @param list<string> $classes
 * @return list<string> the `--sy-` slot variables those classes' rules declare
 */
function slotVariablesSetBy(array $classes): array
{
    $css = supportStylesheet('utilities.css');
    $variables = [];

    foreach ($classes as $class) {
        $selector = preg_quote(str_replace(':', '\:', $class), '/');

        if (preg_match('/^\.' . $selector . '\s*\{(?<declarations>[^}]*)\}/m', $css, $rule) === 1) {
            preg_match_all('/(--sy-[a-z-]+)\s*:/', $rule['declarations'], $declared);

            $variables = [...$variables, ...$declared[1]];
        }
    }

    return $variables;
}

it('emits only shade classes that utilities.css defines', function (array $palette): void {
    $css = supportStylesheet('utilities.css');
    $shadeClasses = itemShadeClasses($palette);

    expect($shadeClasses)->not->toBeEmpty();

    foreach ($shadeClasses as $shadeClass) {
        expect($css)->toContain('.' . str_replace(':', '\:', $shadeClass) . ' {');
    }
})->with('adversarial palettes');

/**
 * The class-defined check above passes for any grid shade under any slot key, so it says nothing
 * about whether the slots are the ones `.sy-ta-text-item` reads. Rename a slot from `text` to
 * `hover:text` and every emitted class still has a rule — it just sets `--sy-hover-text`, leaving
 * `text-(--sy-text)` unset.
 */
it('sets exactly the slot variables text-column.css reads', function (array $palette): void {
    $read = itemSlotVariablesRead();
    $set = slotVariablesSetBy(itemShadeClasses($palette));

    sort($read);
    sort($set);

    expect($set)->toBe(
        $read,
        "ItemComponent's slots and the `.sy-ta-text-item.sy-color` rule disagree on which `--sy-` "
        . 'variables carry the resolved shades. A variable the rule reads and no class sets is a '
        . 'declaration the browser drops; a variable a class sets and no rule reads is a shade '
        . 'resolved for nothing.',
    );
})->with('adversarial palettes');

/**
 * `IconComponent` returns an empty map on purpose — the palette swap is the whole answer and
 * `text-column.css` picks the shade off it (`& > .sy-icon.sy-color { @apply text-color-500; }`).
 * Pinned as an exact set so a slot added later cannot land unmeasured: the moment one does, the
 * icon rule has to read it instead, and this case is what says so.
 */
it('emits only the palette swap for the icon', function (array $palette): void {
    [, $classes] = emittedClasses(IconComponent::class, $palette);

    expect($classes)->toBe(['sy-color', 'sy-color-brand']);
})->with('adversarial palettes');
