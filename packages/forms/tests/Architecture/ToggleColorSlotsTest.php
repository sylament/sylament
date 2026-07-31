<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Architecture;

use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Components\ToggleComponent;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use UnexpectedValueException;

use function dirname;

/**
 * The toggle is the one component whose colour policy ships from support and whose stylesheet
 * ships from here, so neither package's suite sees both halves and only this test ties them
 * together. It scans stylesheets rather than mirroring a class under `src/`, which is what puts it
 * in the Architecture bucket; support's `PolicyShadeGridTest` makes the same two assertions for
 * the components whose stylesheets it owns.
 *
 * Both halves fail in silence. A class `utilities.css` does not define sets no variable, and a
 * variable no class sets makes the declaration reading it invalid at computed-value time — the
 * track paints its unset gray in both cases, with nothing in the console.
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
    return stylesheetAt(dirname(__DIR__, 3) . "/support/assets/src/styles/{$name}");
}

/**
 * Comments are dropped rather than parsed: they sit inside the rules they explain, and a slot
 * named in prose is not a slot anything paints with.
 */
function toggleStylesheet(): string
{
    return preg_replace('#/\*.*?\*/#s', '', stylesheetAt(dirname(__DIR__, 2) . '/assets/src/styles/components/toggle.css')) ?? '';
}

function stylesheetAt(string $path): string
{
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
function toggleShadeClasses(array $palette): array
{
    $colors = new ColorManager(new ContainerBuilder());
    $colors->register(['brand' => $palette]);

    return array_values(array_diff(
        $colors->getComponentClasses(new ToggleComponent($colors), 'brand'),
        ['sy-color', 'sy-color-brand'],
    ));
}

/**
 * The off colour sits on the wrapper and the on colour on the track inside it, so the stylesheet
 * aliases the wrapper's three slots into names the track never redeclares. Reading an alias is
 * reading the slot it points at, which is why they are resolved here rather than counted as
 * variables of their own. One hop is all the file uses; a longer chain would leave the
 * intermediate name in the set and fail the comparison, which is the right way for it to break.
 *
 * @return array<string, string> alias => the slot variable it stands in for
 */
function toggleSlotAliases(): array
{
    preg_match_all('/(--sy-[a-z-]+)\s*:\s*var\(\s*(--sy-[a-z-]+)\s*\)/', toggleStylesheet(), $aliases, PREG_SET_ORDER);

    $aliased = [];

    foreach ($aliases as $alias) {
        $aliased[$alias[1]] = $alias[2];
    }

    return $aliased;
}

/**
 * Grouped by state, because each state paints the whole policy on its own: the checked rules read
 * the slots directly and the unchecked ones read them through the wrapper aliases. Taking the
 * file as one set would let either state hardcode a shade and still be covered by the other.
 *
 * Only the slots a rule paints with count as reads — an alias declaration mentions the slot it
 * stands in for, and the `--sy-bg: initial` resets mention the slots they clear, neither of which
 * puts the shade on screen. So every custom-property declaration is dropped before reading.
 *
 * A rule is a selector run up to its braces, which is all the parsing this file needs: unlike the
 * component stylesheets support pins, the toggle's selectors combine the wrapper, the input and
 * the track rather than nesting.
 *
 * @return array<string, list<string>> state => the slot variables that state's rules read
 */
function toggleSlotVariablesReadByState(): array
{
    $aliases = toggleSlotAliases();

    $states = ['checked' => [], 'unchecked' => []];

    preg_match_all('/(?<selector>[^{}]+)\{(?<declarations>[^{}]*)\}/', toggleStylesheet(), $rules, PREG_SET_ORDER);

    foreach ($rules as $rule) {
        $state = match (true) {
            str_contains($rule['selector'], ':not(:checked)') => 'unchecked',
            str_contains($rule['selector'], ':checked') => 'checked',
            default => null,
        };

        if ($state === null) {
            continue;
        }

        preg_match_all(
            '/\(\s*(--sy-[a-z-]+)\s*\)/',
            preg_replace('/--sy-[a-z-]+\s*:[^;]*;/', '', $rule['declarations']) ?? '',
            $variables,
        );

        $states[$state] = [
            ...$states[$state],
            ...array_map(static fn (string $variable): string => $aliases[$variable] ?? $variable, $variables[1]),
        ];
    }

    return array_map(
        static fn (array $variables): array => array_values(array_unique($variables)),
        $states,
    );
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

    return array_values(array_unique($variables));
}

it('emits only classes that utilities.css defines', function (array $palette): void {
    $css = supportStylesheet('utilities.css');
    $shadeClasses = toggleShadeClasses($palette);

    expect($shadeClasses)->not->toBeEmpty();

    foreach ($shadeClasses as $shadeClass) {
        expect($css)->toContain('.' . str_replace(':', '\:', $shadeClass) . ' {');
    }
})->with('adversarial palettes');

/**
 * The class-defined check above passes for any grid shade under any slot key, so it says nothing
 * about whether the slots are the ones `toggle.css` reads. Swap the policy's `bg` slot for
 * `hover:bg` and every emitted class still has a rule — it just sets `--sy-hover-bg`, leaving the
 * `bg-(--sy-bg)` the checked track paints with unset. Hardcoding that declaration back to
 * `bg-color-600` is the same failure from the other side: a slot resolved for nothing.
 */
it('reads exactly the slot variables the policy resolves, in either state', function (array $palette): void {
    $set = slotVariablesSetBy(toggleShadeClasses($palette));
    sort($set);

    foreach (toggleSlotVariablesReadByState() as $state => $read) {
        sort($read);

        expect($read)->toBe(
            $set,
            "ToggleComponent's slots and the {$state} rules in toggle.css disagree on which `--sy-` "
            . 'variables carry the resolved shades. A variable the rules read and no class sets is a '
            . 'declaration the browser drops; a slot the state never reads is a shade resolved for '
            . 'nothing, and the track paints its unset gray.',
        );
    }
})->with('adversarial palettes');
