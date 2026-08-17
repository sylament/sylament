<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Colors\Components;

use Sylament\Schemas\Colors\Components\TextComponent;
use Sylament\Support\Colors\ColorManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use UnexpectedValueException;

use function dirname;

/**
 * `TextComponent` is the one schemas policy that resolves shades, and both stylesheets it has to
 * agree with ship from support: `utilities.css` defines the shade classes `getComponentClasses()`
 * names, and `components/text.css` reads the slot variables those classes set. Support's own
 * `PolicyShadeGridTest` cannot cover it — a support test cannot see a schemas class — and both
 * links fail in silence: an undefined class sets no variable, a variable nobody sets makes the
 * declaration reading it invalid at computed-value time, and `.sy-sc-text` renders in the
 * inherited colour with nothing in the console.
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

function supportStylesheet(string $name): string
{
    $path = dirname(__DIR__, 5) . "/support/assets/src/styles/{$name}";

    $css = file_get_contents($path);

    if ($css === false) {
        throw new UnexpectedValueException("Unable to read {$path}.");
    }

    return $css;
}

/**
 * @param array<int, string> $palette
 * @return list<string> the emitted classes with the two carriers `getComponentClasses()` always
 *                      prepends removed, leaving only what the colour map resolved
 */
function textShadeClasses(array $palette): array
{
    $colors = new ColorManager(new ContainerBuilder());
    $colors->register(['brand' => $palette]);

    return array_values(array_diff(
        $colors->getComponentClasses(new TextComponent($colors), 'brand'),
        ['sy-color', 'sy-color-brand'],
    ));
}

/**
 * The rule carries Filament's `:not(.sy-color-neutral)` guard, whose neutral arm paints fixed
 * grays and reads no slot, so the pattern tolerates the guard without reading that arm.
 *
 * @return list<string> the `--sy-` slot variables the coloured-text rule reads
 */
function textSlotVariablesRead(): array
{
    if (preg_match('/&\.sy-color(?::not\(\.[a-z-]+\))?\s*\{(?<declarations>[^}]*)\}/', supportStylesheet('components/text.css'), $rule) !== 1) {
        throw new UnexpectedValueException('No `&.sy-color` rule in components/text.css — the coloured-text branch has moved or gone.');
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
    $shadeClasses = textShadeClasses($palette);

    expect($shadeClasses)->not->toBeEmpty();

    foreach ($shadeClasses as $shadeClass) {
        expect($css)->toContain('.' . str_replace(':', '\:', $shadeClass) . ' {');
    }
})->with('adversarial palettes');

/**
 * The class-defined check above passes for any grid shade under any slot key, so it says nothing
 * about whether the slots are the ones `.sy-sc-text` reads. Rename a slot from `text` to
 * `hover:text` and every emitted class still has a rule — it just sets `--sy-hover-text`, leaving
 * `text-(--sy-text)` unset.
 */
it('sets exactly the slot variables text.css reads', function (array $palette): void {
    $read = textSlotVariablesRead();
    $set = slotVariablesSetBy(textShadeClasses($palette));

    sort($read);
    sort($set);

    expect($set)->toBe(
        $read,
        "TextComponent's slots and the `.sy-sc-text.sy-color` rule disagree on which `--sy-` "
        . 'variables carry the resolved shades. A variable the rule reads and no class sets is a '
        . 'declaration the browser drops; a variable a class sets and no rule reads is a shade '
        . 'resolved for nothing.',
    );
})->with('adversarial palettes');
