<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Architecture;

use Sylament\Support\Colors\ColorManager;
use UnexpectedValueException;

use function dirname;

/**
 * The six shipped palettes are written twice — as `ColorManager::DEFAULT_COLORS` in PHP and as
 * `--sy-<palette>-<shade>` custom properties in `index.css` — so a consumer who registers nothing
 * still gets coloured components without calling `sylamentStyles()`. This test is what that
 * duplication was traded for: it is the only thing tying the two copies together, and without it
 * either side can be edited alone and every other test stays green.
 *
 * It reads a stylesheet rather than exercising a class, so it sits in the Architecture bucket
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

/**
 * @return array<string, array<int, string>> palette name => shade => canonical oklch
 */
function cssPaletteFloor(): array
{
    $path = dirname(__DIR__, 2) . '/assets/src/styles/index.css';

    $css = file_get_contents($path);

    if ($css === false) {
        throw new UnexpectedValueException("Unable to read {$path}.");
    }

    if (preg_match('/@layer theme\s*\{\s*:root\s*\{(?<declarations>.*?)\}\s*\}/s', $css, $block) !== 1) {
        throw new UnexpectedValueException("No `@layer theme { :root { … } }` block in {$path} — the palette floor has moved or gone.");
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
