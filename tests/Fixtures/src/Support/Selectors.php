<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Support;

/**
 * The looks support's components give any area's markup: colour, size, button, icon
 * button and link, named by the `sy-` class the stylesheet paints them by.
 */
final class Selectors
{
    /**
     * Gray paints an element carrying no colour class, so a gray element is an uncoloured one.
     */
    public const string GRAY = ':not(.sy-color)';

    public const string MEDIUM = '.sy-size-md';

    public const string BUTTON = '.sy-btn';

    public const string SMALL_ICON_BUTTON = '.sy-icon-btn.sy-size-sm';

    public const string LINK = '.sy-link';

    public const string SMALL_LINK = '.sy-link.sy-size-sm';

    public static function color(string $name): string
    {
        return ".sy-color-{$name}";
    }
}
