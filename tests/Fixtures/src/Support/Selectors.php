<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Support;

/**
 * The standalone-root marker, and the looks support's components give any area's markup:
 * colour, palette, size, font, alignment, state, the hidden and wrapped looks, button, button
 * group, icon button, icon, link, badge and its label, breadcrumb separator, a fieldset with its
 * hidden label and required mark, a section's container, variants and collapsibility, a tabs
 * layout, the active tab or step, the screen-reader-only text and the cloak a persisted state
 * hides behind until its controller connects, named by the `sy-` class or custom property the
 * stylesheet paints them by; the grid itself, the column counts, spans, starts and orders
 * support's grid gives a schema, and the bare counts and direction class of a column-wise grid,
 * by the custom property or class the stylesheet lays its grid out by; a section's Stimulus
 * controller and the values it reads, and the sortable controller with its handles, items and
 * animation duration, by the `data-` hooks it reads; a section's collapse button by its place
 * and its state, whose name changes; the modal's dialog, an open one and the heading,
 * description, icon, footer actions and submit read off it, its window, width, footer, slide-over,
 * sticky parts and the clickable, click-through and overlaid looks, a link's label, the outlined
 * look, and a dropdown, its trigger, its panel and its items by the `sy-` classes the stylesheet
 * paints them by, and its controller; the modal's close, the badge's delete and the pagination's chevron buttons by their names;
 * the values the modal controller reads, and a dropdown's placement, offset, flip and teleport by
 * the Stimulus values its controller reads.
 * The input wrapper, its invalid state and each side of it are named by their `sy-` classes,
 * because which side an affix sits on is the stylesheet's contract; what sits there is asserted
 * by its text, its role or its element.
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

    public const string HIDDEN = '.sy-hidden';

    public const string WRAPPED = '.sy-wrapped';

    public const string INVALID = '.sy-invalid';

    public const string SCREEN_READER_ONLY = '.sy-sr-only';

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

    public const string CONTAINED = '.sy-contained';

    public const string VERTICAL = '.sy-vertical';

    public const string ACTIVE = '.sy-active';

    public const string CLOAK = '.sy-cloak';

    public const string SECTION_CONTROLLER = '[data-controller="sylament--support--section"]';

    public const string SORTABLE_CONTROLLER = '[data-controller~="sylament--support--sortable"]';

    public const string SORTABLE_HANDLE = '[data-sortable-handle]';

    public const string SORTABLE_ITEM = '[data-sortable-item]';

    public const string FIELDSET = '.sy-fieldset';

    public const string FIELDSET_LABEL_HIDDEN = '.sy-fieldset-label-hidden';

    public const string FIELDSET_REQUIRED_MARK = '.sy-fieldset-label-required-mark';

    /**
     * A grid of one column at every breakpoint: the default map and nothing over it.
     */
    public const string ONE_COLUMN = '[style="--cols-default: repeat(1, minmax(0, 1fr))"]';

    /**
     * A cell given a column span at any breakpoint.
     */
    public const string ANY_COLUMN_SPAN = '[style*="--col-span-"]';

    public const string GRID = '.sy-grid';

    public const string GRID_DIRECTION_COLUMN = '.sy-grid-direction-col';

    public const string BUTTON = '.sy-btn';

    public const string BUTTON_GROUP = '.sy-btn-group';

    public const string ICON_BUTTON = '.sy-icon-btn';

    public const string SMALL_ICON_BUTTON = self::ICON_BUTTON . '.sy-size-sm';

    public const string ICON = '.sy-icon';

    public const string LINK = '.sy-link';

    public const string SMALL_LINK = '.sy-link.sy-size-sm';

    public const string BADGE = '.sy-badge';

    public const string BADGE_LABEL = '.sy-badge-label';

    public const string BREADCRUMB_SEPARATOR = '.sy-breadcrumbs-item-separator.sy-ltr';

    public const string RTL_BREADCRUMB_SEPARATOR = '.sy-breadcrumbs-item-separator.sy-rtl';

    public const string SECTION_COLLAPSE_BUTTON = 'header button[aria-expanded]';

    public const string BADGE_DELETE_BUTTON = 'button[aria-label="Remove"]';

    public const string MODAL = 'dialog.sy-modal';

    public const string OPEN_MODAL = 'dialog.sy-modal[open]';

    public const string MODAL_HEADING = self::OPEN_MODAL . ' .sy-modal-heading';

    public const string MODAL_DESCRIPTION = self::OPEN_MODAL . ' .sy-modal-description';

    public const string MODAL_ICON = self::OPEN_MODAL . ' .sy-modal-icon-ctn svg';

    public const string MODAL_FOOTER_ACTIONS = self::OPEN_MODAL . ' .sy-modal-footer-actions button';

    public const string MODAL_SUBMIT = self::MODAL_FOOTER_ACTIONS . '[type="submit"]';

    public const string MODAL_WINDOW = '.sy-modal-window';

    public const string MODAL_FOOTER = '.sy-modal-footer';

    public const string MODAL_CLICKABLE = '.sy-clickable';

    public const string MODAL_CLICK_THROUGH = '.sy-modal-click-through';

    public const string MODAL_OVERLAID = '.sy-modal-overlaid';

    public const string MODAL_CLOSE_BUTTON = 'button[aria-label="Close"]';

    public const string FIRST_PAGE_BUTTON = 'button[aria-label="Go to the first page"]';

    public const string PREVIOUS_PAGE_BUTTON = 'button[aria-label="Previous page"]';

    public const string NEXT_PAGE_BUTTON = 'button[aria-label="Next page"]';

    public const string LAST_PAGE_BUTTON = 'button[aria-label="Go to the last page"]';

    public const string OUTLINED = '.sy-outlined';

    public const string LINK_LABEL = '.sy-link-label';

    public const string DROPDOWN = '.sy-dropdown';

    public const string DROPDOWN_CONTROLLER = '[data-controller="sylament--support--dropdown"]';

    public const string DROPDOWN_TRIGGER = '.sy-dropdown-trigger';

    public const string DROPDOWN_PANEL = '.sy-dropdown-panel';

    public const string DROPDOWN_ITEM = '.sy-dropdown-item';

    public const string TELEPORTED_DROPDOWN = '[data-sylament--support--dropdown-teleport-value="true"]';

    public const string KEPT_DROPDOWN = '[data-sylament--support--dropdown-teleport-value="false"]';

    public const string INPUT_WRAPPER = '.sy-input-wrp';

    public const string PREFIX = '.sy-input-wrp-prefix';

    public const string SUFFIX = '.sy-input-wrp-suffix';

    public const string INLINE_PREFIX = '.sy-input-wrp-prefix.sy-inline';

    public const string INLINE_SUFFIX = '.sy-input-wrp-suffix.sy-inline';

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

    /**
     * A column-wise grid's count at a breakpoint, which it gives as a bare number rather than a track list.
     */
    public static function columnWiseColumns(string $breakpoint, int $count): string
    {
        return "[style*=\"--cols-{$breakpoint}: {$count}\"]";
    }

    /**
     * A cell spanning a number of columns at a breakpoint, the full row when told `full`, or any span there without a span.
     *
     * @param int|'full'|null $span
     */
    public static function columnSpan(string $breakpoint, int|string|null $span = null): string
    {
        return match ($span) {
            null => "[style*=\"--col-span-{$breakpoint}:\"]",
            'full' => "[style*=\"--col-span-{$breakpoint}: 1 / -1\"]",
            default => "[style*=\"--col-span-{$breakpoint}: span {$span} / span {$span}\"]",
        };
    }

    public static function columnStart(string $breakpoint, int $column): string
    {
        return "[style*=\"--col-start-{$breakpoint}: {$column}\"]";
    }

    public static function columnOrder(string $breakpoint, int $order): string
    {
        return "[style*=\"--col-order-{$breakpoint}: {$order}\"]";
    }

    /**
     * A value the section controller reads, or, without one, the value's presence.
     */
    public static function sectionValue(string $name, ?string $value = null): string
    {
        return $value === null ? "[data-sylament--support--section-{$name}-value]" : "[data-sylament--support--section-{$name}-value=\"{$value}\"]";
    }

    /**
     * A value the dropdown controller reads: its placement, offset or flip.
     */
    public static function dropdownValue(string $name, string $value): string
    {
        return "[data-sylament--support--dropdown-{$name}-value=\"{$value}\"]";
    }

    /**
     * A value the modal controller reads, or, without one, the value's presence.
     */
    public static function modalValue(string $name, ?string $value = null): string
    {
        return $value === null ? "[data-sylament--support--modal-{$name}-value]" : "[data-sylament--support--modal-{$name}-value=\"{$value}\"]";
    }

    /**
     * A modal window's width, which the dialog repeats for a full-screen one.
     */
    public static function modalWidth(string $width): string
    {
        return ".sy-modal-{$width}";
    }

    /**
     * A slide-over, or one sliding in from the side it names.
     */
    public static function modalSlideOver(?string $from = null): string
    {
        return $from === null ? '.sy-modal-slide-over' : ".sy-modal-slide-over-from-{$from}";
    }

    /**
     * A modal whose header or footer stays in view while its content scrolls.
     */
    public static function modalSticky(string $part): string
    {
        return ".sy-modal-has-sticky-{$part}";
    }

    public static function sortableAnimationDuration(string $milliseconds): string
    {
        return "[data-sylament--support--sortable-animation-duration-value=\"{$milliseconds}\"]";
    }

    public static function verticalAlign(string $value): string
    {
        return ".sy-vertical-align-{$value}";
    }
}
