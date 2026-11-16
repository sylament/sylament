<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Support;

/**
 * The standalone-root marker, and the looks support's components give any area's markup:
 * colour, palette, size, font, alignment, state, button, icon button, icon, link, badge,
 * breadcrumb separator and a section's container, variants and collapsibility, named by the `sy-` class or custom
 * property the stylesheet paints them by; the column counts support's grid gives a schema, by
 * the custom property the stylesheet lays its grid out by; a section's collapse button by its place and its state, whose name changes;
 * the modal's close, the badge's delete and the pagination's chevron buttons by their
 * names, and a dropdown's teleport by its Stimulus value. A side of the input wrapper is
 * named by its `sy-` class, because which side an affix sits on is the stylesheet's
 * contract; what sits there is asserted by its text, its role or its element.
 */
final class Selectors
{
    /**
     * The bare `sy` marker on a component root that can render standalone, which the scoped preflight matches.
     */
    public const string STANDALONE_ROOT = '.sy';

    /**
     * Gray paints an element carrying no colour class, so a gray element is an uncoloured one:
     * neither the `sy-color` marker, a named colour nor a text shade.
     */
    public const string GRAY = ':not([class*="color"])';

    public const string COLORED = '.sy-color';

    public const string MEDIUM = '.sy-size-md';

    public const string UNSIZED = ':not([class*="sy-size-"])';

    public const string DISABLED = '.sy-disabled';

    /**
     * Neither a font weight nor a font family, which share the `sy-font-` prefix.
     */
    public const string NO_FONT = ':not([class*="sy-font-"])';

    public const string NO_ALIGN = ':not([class*="sy-align-"])';

    public const string NO_VERTICAL_ALIGN = ':not([class*="sy-vertical-align-"])';

    public const string NOT_CONTAINED = '.sy-section-not-contained';

    public const string ASIDE = '.sy-aside';

    public const string COMPACT = '.sy-compact';

    public const string DIVIDED = '.sy-divided';

    public const string SECONDARY = '.sy-secondary';

    public const string CONTENT_BEFORE = '.sy-section-has-content-before';

    public const string COLLAPSIBLE = '.sy-collapsible';

    public const string COLLAPSED = '.sy-collapsed';

    /**
     * A grid of one column at every breakpoint: the default map and nothing over it.
     */
    public const string ONE_COLUMN = '[style="--cols-default: repeat(1, minmax(0, 1fr))"]';

    public const string BUTTON = '.sy-btn';

    public const string ICON_BUTTON = '.sy-icon-btn';

    public const string SMALL_ICON_BUTTON = self::ICON_BUTTON . '.sy-size-sm';

    public const string ICON = '.sy-icon';

    public const string LINK = '.sy-link';

    public const string SMALL_LINK = '.sy-link.sy-size-sm';

    public const string BADGE = '.sy-badge';

    public const string BREADCRUMB_SEPARATOR = '.sy-breadcrumbs-item-separator.sy-ltr';

    public const string RTL_BREADCRUMB_SEPARATOR = '.sy-breadcrumbs-item-separator.sy-rtl';

    public const string SECTION_COLLAPSE_BUTTON = 'header button[aria-expanded]';

    public const string BADGE_DELETE_BUTTON = 'button[aria-label="Remove"]';

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

    /**
     * A colour given as a value rather than a name, painted through the palette's 500 shade.
     */
    public static function palette(string $shade500): string
    {
        return "[style*=\"--sy-color-500: {$shade500};\"]";
    }

    public static function textShades(int $light, int $dark): string
    {
        return "[class~=\"sy-text-color-{$light}\"][class~=\"dark:sy-text-color-{$dark}\"]";
    }

    public static function size(string $name): string
    {
        return ".sy-size-{$name}";
    }

    public static function font(string $name): string
    {
        return ".sy-font-{$name}";
    }

    public static function align(string $value): string
    {
        return ".sy-align-{$value}";
    }

    public static function columns(string $breakpoint, int $count): string
    {
        return "[style*=\"--cols-{$breakpoint}: repeat({$count}, minmax(0, 1fr))\"]";
    }

    public static function verticalAlign(string $value): string
    {
        return ".sy-vertical-align-{$value}";
    }
}
