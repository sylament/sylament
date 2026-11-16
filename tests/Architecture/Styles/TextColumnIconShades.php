<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Styles;

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use UnexpectedValueException;

/**
 * Reads the shades the text column's icons are painted with out of the stylesheets that hardcode them, tables'
 * `text-column.css` for the item icon and support's `badge.css` for a gray badge's icon, so a shade edit moves the
 * measurement instead of quietly invalidating it.
 */
final class TextColumnIconShades
{
    /**
     * Each icon pairing that misses the non-text ratio over the shipped palettes: the coloured item icon on every
     * palette and the uncoloured one against the table chrome's `bg-white dark:bg-gray-900`, and the gray badge's icon
     * against its own light background; the badge's dark background is an alpha overlay this ratio cannot express.
     *
     * @return list<string>
     */
    public static function failingPairings(string $textColumnCss, string $badgeCss): array
    {
        $shades = self::read($textColumnCss, $badgeCss);
        $gray = ColorManager::DEFAULT_COLORS['gray'];
        $surfaces = ['light' => 'oklch(1 0 0)', 'dark' => $gray[900]];
        $pairings = [];

        foreach (ColorManager::DEFAULT_COLORS as $palette => $colors) {
            foreach ($surfaces as $mode => $surface) {
                $pairings["coloured item icon · {$palette} {$mode}"] = [$surface, $colors[$shades['itemColor']]];
            }
        }

        [$itemLight, $itemDark] = $shades['itemGray'];
        $pairings['uncoloured item icon light'] = [$surfaces['light'], $gray[$itemLight]];
        $pairings['uncoloured item icon dark'] = [$surfaces['dark'], $gray[$itemDark]];

        [$badgeLight] = $shades['badgeGray'];
        $pairings['gray badge icon light'] = [$gray[$shades['badgeSurface']], $gray[$badgeLight]];

        return array_keys(array_filter(
            $pairings,
            static fn (array $pairing): bool => Color::calculateContrastRatio(...$pairing) < Color::WCAG_AA_NON_TEXT,
        ));
    }

    /**
     * The uncoloured item icon's light and dark gray, the coloured item icon's palette shade, the gray badge's light
     * background and its icon's light and dark gray.
     *
     * @return array{itemGray: array{int, int}, itemColor: int, badgeSurface: int, badgeGray: array{int, int}}
     */
    private static function read(string $textColumnCss, string $badgeCss): array
    {
        $item = mb_strstr($textColumnCss, '& > .sy-icon {');

        if ($item === false) {
            throw new UnexpectedValueException('No `& > .sy-icon` rule in the text-column stylesheet: the item icon has moved or gone.');
        }

        if (preg_match('/@apply[^;]*\btext-gray-(?<light>\d+)\b[^;]*\bdark:text-gray-(?<dark>\d+)\b[^;]*;/', $item, $itemGray) !== 1) {
            throw new UnexpectedValueException('The item icon no longer applies a light and a dark gray shade.');
        }

        if (preg_match('/&\.sy-color\s*\{\s*@apply\s+(?<utilities>[^;]+);/', $item, $itemColor) !== 1) {
            throw new UnexpectedValueException('No `&.sy-color` branch under the item icon rule.');
        }

        if (preg_match('/^text-color-(?<shade>\d+)$/', mb_trim($itemColor['utilities']), $itemColorShade) !== 1) {
            throw new UnexpectedValueException("The coloured item icon applies [{$itemColor['utilities']}] where a single `text-color-<shade>` is measured.");
        }

        $badge = mb_strstr($badgeCss, '&:not(.sy-color) {');

        if ($badge === false) {
            throw new UnexpectedValueException('No `&:not(.sy-color)` rule in the badge stylesheet: the gray badge has moved or gone.');
        }

        if (preg_match('/@apply[^;]*\bbg-gray-(?<shade>\d+)\b[^;]*;/', $badge, $badgeSurface) !== 1) {
            throw new UnexpectedValueException('The gray badge no longer paints a gray background shade.');
        }

        if (preg_match('/&\s+\.sy-icon\s*\{\s*@apply\s+text-gray-(?<light>\d+)\s+dark:text-gray-(?<dark>\d+);/', $badge, $badgeGray) !== 1) {
            throw new UnexpectedValueException('The gray badge icon no longer applies a light and a dark gray shade.');
        }

        return [
            'itemGray' => [(int) $itemGray['light'], (int) $itemGray['dark']],
            'itemColor' => (int) $itemColorShade['shade'],
            'badgeSurface' => (int) $badgeSurface['shade'],
            'badgeGray' => [(int) $badgeGray['light'], (int) $badgeGray['dark']],
        ];
    }
}
