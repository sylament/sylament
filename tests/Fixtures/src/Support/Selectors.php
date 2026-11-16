<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Support;

/**
 * The looks support's components give any area's markup: colour, size, font, state,
 * button, icon button, icon, link, badge, breadcrumb separator and a section's container,
 * named by the `sy-` class the stylesheet paints them by; the modal's close button and the pagination's chevron
 * buttons by their names, and a dropdown's teleport by its Stimulus value. A side of the
 * input wrapper is named by its `sy-` class, because which side an affix sits on is the
 * stylesheet's contract; what sits there is asserted by its text, its role or its element.
 */
final class Selectors
{
    /**
     * Gray paints an element carrying no colour class, so a gray element is an uncoloured one.
     */
    public const string GRAY = ':not(.sy-color)';

    public const string COLORED = '.sy-color';

    public const string MEDIUM = '.sy-size-md';

    public const string DISABLED = '.sy-disabled';

    /**
     * Neither a font weight nor a font family, which share the `sy-font-` prefix.
     */
    public const string NO_FONT = ':not([class*="sy-font-"])';

    public const string NOT_CONTAINED = '.sy-section-not-contained';

    public const string BUTTON = '.sy-btn';

    public const string SMALL_ICON_BUTTON = '.sy-icon-btn.sy-size-sm';

    public const string ICON = '.sy-icon';

    public const string LINK = '.sy-link';

    public const string SMALL_LINK = '.sy-link.sy-size-sm';

    public const string BADGE = '.sy-badge';

    public const string BREADCRUMB_SEPARATOR = '.sy-breadcrumbs-item-separator.sy-ltr';

    public const string RTL_BREADCRUMB_SEPARATOR = '.sy-breadcrumbs-item-separator.sy-rtl';

    public const string MODAL_CLOSE_BUTTON = 'button[aria-label="Close"]';

    public const string FIRST_PAGE_BUTTON = 'button[aria-label="Go to the first page"]';

    public const string PREVIOUS_PAGE_BUTTON = 'button[aria-label="Previous page"]';

    public const string NEXT_PAGE_BUTTON = 'button[aria-label="Next page"]';

    public const string LAST_PAGE_BUTTON = 'button[aria-label="Go to the last page"]';

    public const string TELEPORTED_DROPDOWN = '[data-sylament--support--dropdown-teleport-value="true"]';

    public const string KEPT_DROPDOWN = '[data-sylament--support--dropdown-teleport-value="false"]';

    public const string PREFIX = '.sy-input-wrp-prefix';

    public const string SUFFIX = '.sy-input-wrp-suffix';

    public const string INLINE_PREFIX = '.sy-input-wrp-prefix.sy-inline';

    public const string PREFIX_ICON = '.sy-input-wrp-prefix svg';

    public const string SUFFIX_ICON = '.sy-input-wrp-suffix svg';

    public const string PREFIX_ACTIONS = '.sy-input-wrp-prefix button';

    public const string SUFFIX_ACTIONS = '.sy-input-wrp-suffix button';

    public static function color(string $name): string
    {
        return ".sy-color-{$name}";
    }

    public static function size(string $name): string
    {
        return ".sy-size-{$name}";
    }

    public static function font(string $name): string
    {
        return ".sy-font-{$name}";
    }
}
