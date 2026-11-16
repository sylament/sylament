<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Styles;

use UnexpectedValueException;

/**
 * Reads the shades the text column's icons are painted with out of the stylesheets that hardcode them, tables'
 * `text-column.css` for the item icon and support's `badge.css` for a gray badge's icon, so a shade edit moves the
 * measurement instead of quietly invalidating it.
 */
final class TextColumnIconShades
{
    /**
     * The uncoloured item icon's light and dark gray, the coloured item icon's palette shade, the gray badge's light
     * background and its icon's light and dark gray.
     *
     * @return array{itemGray: array{int, int}, itemColor: int, badgeSurface: int, badgeGray: array{int, int}}
     */
    public static function read(): array
    {
        $item = mb_strstr(PaletteCopies::stylesheet('tables', 'components/text-column.css'), '& > .sy-icon {');

        if ($item === false) {
            throw new UnexpectedValueException('No `& > .sy-icon` rule in tables components/text-column.css: the item icon has moved or gone.');
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

        $badge = mb_strstr(PaletteCopies::stylesheet('support', 'components/badge.css'), '&:not(.sy-color) {');

        if ($badge === false) {
            throw new UnexpectedValueException('No `&:not(.sy-color)` rule in support components/badge.css: the gray badge has moved or gone.');
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
