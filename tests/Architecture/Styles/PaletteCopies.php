<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Styles;

use Sylament\Support\Colors\ColorManager;
use UnexpectedValueException;

/**
 * Reads the four copies of the six shipped palettes: `ColorManager::DEFAULT_COLORS`, and in support's stylesheets
 * the `:root` floor, the `@theme inline` bridge and the `.sy-color-<palette>` swap classes.
 */
final class PaletteCopies
{
    /**
     * The two copies differ in notation — CSS writes lightness as a percentage (`66.6%`) where
     * `Color` writes a decimal (`0.666`) — so every channel is compared as a number, not a string.
     */
    public static function canonicalOklch(string $value): string
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
     * Neither the bridge nor the swap classes repeat a colour value: each declaration indirects
     * through the `--sy-<palette>-<shade>` custom property, which is what lets a consumer retheme by
     * overriding those properties instead of recompiling.
     *
     * @return array<int, string> shade => the variable reference both copies are expected to carry
     */
    public static function shadeVariables(string $palette): array
    {
        return array_combine(
            ColorManager::SHADES,
            array_map(static fn (int $shade): string => "var(--sy-{$palette}-{$shade})", ColorManager::SHADES),
        );
    }

    /**
     * @return array<string, array<int, string>> palette name => shade => canonical oklch
     */
    public static function cssFloor(): array
    {
        $css = self::stylesheet('support', 'index.css');

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
            $palettes[$declaration['palette']][(int) $declaration['shade']] = self::canonicalOklch($declaration['value']);
        }

        return self::sort($palettes);
    }

    /**
     * @return array<string, array<int, string>> palette name => shade => declared value
     */
    public static function themeBridge(): array
    {
        $css = self::stylesheet('support', 'index.css');

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

        return self::sort($palettes);
    }

    /**
     * @return array<string, array<int, string>> palette name => shade => declared value
     */
    public static function swapClasses(): array
    {
        preg_match_all(
            '/^\.sy-color-(?<palette>[a-z]+)\s*\{(?<declarations>[^}]*)\}/m',
            self::stylesheet('support', 'utilities.css'),
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

        return self::sort($palettes);
    }

    /**
     * @return array<string, array<int, string>> palette name => shade => canonical oklch
     */
    public static function phpFloor(): array
    {
        return self::sort(array_map(
            static fn (array $shades): array => array_map(self::canonicalOklch(...), $shades),
            ColorManager::DEFAULT_COLORS,
        ));
    }

    /**
     * @param array<string, array<int, string>> $palettes
     *
     * @return array<string, array<int, string>>
     */
    private static function sort(array $palettes): array
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
     * @param string $name the path under the package's `assets/src/styles/`
     */
    public static function stylesheet(string $package, string $name): string
    {
        $path = self::stylesheetPath($package, $name);

        $css = file_get_contents($path);

        if ($css === false) {
            throw new UnexpectedValueException("Unable to read {$path}.");
        }

        return $css;
    }

    public static function stylesheetPath(string $package, string $name): string
    {
        return dirname(__DIR__, 3) . "/packages/{$package}/assets/src/styles/{$name}";
    }
}
