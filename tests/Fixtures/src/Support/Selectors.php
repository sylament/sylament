<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Support;

/**
 * The looks support's components give any area's markup: colour, size, state, button,
 * icon button, icon, link, badge and breadcrumb separator, named by the `sy-` class the
 * stylesheet paints them by; the modal's close button by its name, and a dropdown's
 * teleport by its Stimulus value.
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

    public const string BUTTON = '.sy-btn';

    public const string SMALL_ICON_BUTTON = '.sy-icon-btn.sy-size-sm';

    public const string ICON = '.sy-icon';

    public const string LINK = '.sy-link';

    public const string SMALL_LINK = '.sy-link.sy-size-sm';

    public const string BADGE = '.sy-badge';

    public const string BREADCRUMB_SEPARATOR = '.sy-breadcrumbs-item-separator.sy-ltr';

    public const string RTL_BREADCRUMB_SEPARATOR = '.sy-breadcrumbs-item-separator.sy-rtl';

    public const string MODAL_CLOSE_BUTTON = 'button[aria-label="Close"]';

    public const string TELEPORTED_DROPDOWN = '[data-sylament--support--dropdown-teleport-value="true"]';

    public const string KEPT_DROPDOWN = '[data-sylament--support--dropdown-teleport-value="false"]';

    public static function color(string $name): string
    {
        return ".sy-color-{$name}";
    }

    public static function size(string $name): string
    {
        return ".sy-size-{$name}";
    }
}
