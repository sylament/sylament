<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Architecture;

use Sylament\Support\Colors\ColorManager;
use UnexpectedValueException;

use function dirname;

/**
 * The six shipped palettes are named in four places — `ColorManager::DEFAULT_COLORS` in PHP, and in
 * CSS the `:root` floor that gives a consumer who registers nothing coloured components without
 * calling `sylamentStyles()`, the `@theme inline` bridge that makes `@apply bg-<palette>-600`
 * compile, and the `.sy-color-<palette>` swap classes that point the dynamic `color` palette at
 * one of them. This test is what that duplication was traded for: it is the only thing tying the
 * four copies together, and without it any one of them can be edited alone and every other test
 * stays green.
 *
 * It reads stylesheets rather than exercising a class, so it sits in the Architecture bucket
 * rather than mirroring `src/`.
 */

/**
 * The two copies differ in notation — CSS writes lightness as a percentage (`66.6%`) where
 * `Color` writes a decimal (`0.666`) — so every channel is compared as a number, not a string.
 */
function canonicalOklch(string $value): string
{
    $value = mb_trim($value);

    if (preg_match('/^oklch\(\s*(?<lightness>-?[\d.]+%?)\s+(?<chroma>-?[\d.]+)\s+(?<hue>-?[\d.]+)\s*\)$/', $value, $channels) !== 1) {
        throw new UnexpectedValueException("Unable to parse the oklch value \"{$value}\".");
    }

    $lightness = str_ends_with($channels['lightness'], '%')
        ? ((float) mb_rtrim($channels['lightness'], '%')) / 100
        : (float) $channels['lightness'];

    return sprintf('oklch(%.6f %.6f %.6f)', $lightness, (float) $channels['chroma'], (float) $channels['hue']);
}

/**
 * @param array<string, array<int, string>> $palettes
 * @return array<string, array<int, string>>
 */
function sortPalettes(array $palettes): array
{
    $palettes = array_map(
        static function (array $shades): array {
            ksort($shades);

            return $shades;
        },
        $palettes,
    );

    ksort($palettes);

    return $palettes;
}

function stylesheet(string $name): string
{
    $path = dirname(__DIR__, 2) . "/assets/src/styles/{$name}";

    $css = file_get_contents($path);

    if ($css === false) {
        throw new UnexpectedValueException("Unable to read {$path}.");
    }

    return $css;
}

/**
 * Neither the bridge nor the swap classes repeat a colour value: each declaration indirects
 * through the `--sy-<palette>-<shade>` custom property, which is what lets a consumer retheme by
 * overriding those properties instead of recompiling.
 *
 * @return array<int, string> shade => the variable reference both copies are expected to carry
 */
function shadeVariables(string $palette): array
{
    return array_combine(
        ColorManager::SHADES,
        array_map(static fn (int $shade): string => "var(--sy-{$palette}-{$shade})", ColorManager::SHADES),
    );
}

/**
 * @return array<string, array<int, string>> palette name => shade => canonical oklch
 */
function cssPaletteFloor(): array
{
    $css = stylesheet('index.css');

    if (preg_match('/@layer theme\s*\{\s*:root\s*\{(?<declarations>.*?)\}\s*\}/s', $css, $block) !== 1) {
        throw new UnexpectedValueException('No `@layer theme { :root { … } }` block in index.css — the palette floor has moved or gone.');
    }

    preg_match_all(
        '/--sy-(?<palette>[a-z]+)-(?<shade>\d+)\s*:\s*(?<value>[^;]+);/',
        $block['declarations'],
        $declarations,
        PREG_SET_ORDER,
    );

    $palettes = [];

    foreach ($declarations as $declaration) {
        $palettes[$declaration['palette']][(int) $declaration['shade']] = canonicalOklch($declaration['value']);
    }

    return sortPalettes($palettes);
}

/**
 * @return array<string, array<int, string>> palette name => shade => declared value
 */
function cssThemeBridge(): array
{
    $css = stylesheet('index.css');

    if (preg_match('/@theme inline\s*\{(?<declarations>[^}]*)\}/', $css, $block) !== 1) {
        throw new UnexpectedValueException('No `@theme inline { … }` block in index.css — the Tailwind colour bridge has moved or gone.');
    }

    preg_match_all(
        '/--color-(?<palette>[a-z]+)-(?<shade>\d+)\s*:\s*(?<value>[^;]+);/',
        $block['declarations'],
        $declarations,
        PREG_SET_ORDER,
    );

    $palettes = [];

    foreach ($declarations as $declaration) {
        $palettes[$declaration['palette']][(int) $declaration['shade']] = mb_trim($declaration['value']);
    }

    return sortPalettes($palettes);
}

/**
 * @return array<string, array<int, string>> palette name => shade => declared value
 */
function cssColorSwapClasses(): array
{
    preg_match_all(
        '/^\.sy-color-(?<palette>[a-z]+)\s*\{(?<declarations>[^}]*)\}/m',
        stylesheet('utilities.css'),
        $rules,
        PREG_SET_ORDER,
    );

    $palettes = [];

    foreach ($rules as $rule) {
        preg_match_all(
            '/--sy-color-(?<shade>\d+)\s*:\s*(?<value>[^;]+);/',
            $rule['declarations'],
            $declarations,
            PREG_SET_ORDER,
        );

        $palettes[$rule['palette']] = [];

        foreach ($declarations as $declaration) {
            $palettes[$rule['palette']][(int) $declaration['shade']] = mb_trim($declaration['value']);
        }
    }

    return sortPalettes($palettes);
}

/**
 * @return array<string, array<int, string>> palette name => shade => canonical oklch
 */
function phpPaletteFloor(): array
{
    return sortPalettes(array_map(
        static fn (array $shades): array => array_map(canonicalOklch(...), $shades),
        ColorManager::DEFAULT_COLORS,
    ));
}

it('defines a :root palette for exactly the colours DEFAULT_COLORS ships', function (): void {
    expect(array_keys(cssPaletteFloor()))->toBe(
        array_keys(phpPaletteFloor()),
        'index.css and ColorManager::DEFAULT_COLORS disagree on which palettes exist. A palette in '
        . 'one and not the other renders as either dead CSS or an uncoloured component.',
    );
});

it('matches DEFAULT_COLORS shade for shade', function (string $palette): void {
    expect(cssPaletteFloor()[$palette] ?? [])->toBe(
        phpPaletteFloor()[$palette],
        "The `{$palette}` palette in index.css no longer matches ColorManager::DEFAULT_COLORS. The "
        . 'PHP copy is what the contrast policies measure against and the CSS copy is what the '
        . 'browser paints, so any drift means shades are chosen for colours that are not on screen.',
    );
})->with(array_keys(ColorManager::DEFAULT_COLORS));

it('bridges a Tailwind token for exactly the floor palettes and the dynamic one', function (): void {
    $expected = [...array_keys(ColorManager::DEFAULT_COLORS), 'color'];
    sort($expected);

    expect(array_keys(cssThemeBridge()))->toBe(
        $expected,
        'The `@theme inline` block in index.css and ColorManager::DEFAULT_COLORS disagree on which '
        . 'palettes exist. Without a bridged token, `@apply bg-<palette>-600` does not compile, so '
        . 'a palette the floor defines paints nothing.',
    );
});

it('bridges every palette shade by shade', function (string $palette): void {
    expect(cssThemeBridge()[$palette] ?? [])->toBe(
        shadeVariables($palette),
        "The `{$palette}` bridge in index.css no longer maps every shade of the grid onto its own "
        . '`--sy-` property. A missing or misdirected shade compiles to a declaration reading an '
        . 'unset variable, which the browser drops at computed-value time and reports nowhere.',
    );
})->with([...array_keys(ColorManager::DEFAULT_COLORS), 'color']);

it('ships a swap class for exactly the palettes DEFAULT_COLORS defines', function (): void {
    expect(array_keys(cssColorSwapClasses()))->toBe(
        array_keys(phpPaletteFloor()),
        'The `.sy-color-<palette>` rules in utilities.css and ColorManager::DEFAULT_COLORS disagree '
        . 'on which palettes exist. `getComponentClasses()` emits one of those class names for '
        . 'every coloured component, and without a rule to match it the dynamic `color` palette '
        . 'stays unset and the component renders uncoloured.',
    );
});

it('swaps every palette shade by shade', function (string $palette): void {
    expect(cssColorSwapClasses()[$palette] ?? [])->toBe(
        shadeVariables($palette),
        "The `.sy-color-{$palette}` rule in utilities.css no longer points every shade of the grid "
        . 'at its own `--sy-` property. A shade left out is a shade the resolved colour map can '
        . 'still choose, and the carrier class reading it then sets its slot to nothing.',
    );
})->with(array_keys(ColorManager::DEFAULT_COLORS));
