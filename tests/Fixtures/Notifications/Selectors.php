<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Notifications;

/**
 * The notifications area's render selectors. The stack is found by its role, a toast by
 * its Stimulus controller, its title by its heading and its close button by its name;
 * the body, the icon and the actions row have no role to name them, so each is named by
 * the `sy-` class the stylesheet lays it out by, as are the look and the size of an
 * action and the placement of the stack.
 */
final class Selectors
{
    public const string STACK = '[role="status"]';

    public const string STACK_RECEIVING_TOASTS = self::STACK . '[aria-atomic="false"][data-action~="sylament:notification@window->sylament--notifications--notifications#receive"]';

    public const string TOAST = '[data-controller~="sylament--notifications--notification"]';

    public const string TOAST_HANDLING_ACTION_CLICKS = self::TOAST . '[data-action~="click->sylament--notifications--notification#handleActionClick"]';

    public const string DURATION = 'data-sylament--notifications--notification-duration-value';

    public const string TITLE = self::TOAST . ' h3';

    public const string BODY = '.sy-no-notification-body';

    public const string ICON = '.sy-no-notification-icon';

    public const string ACTIONS = '.sy-no-notification-actions';

    public const string CLOSE_BUTTON = 'button[aria-label="Close notification"]';

    public const string CLOSING_BUTTON = self::CLOSE_BUTTON . '[data-action~="sylament--notifications--notification#close"]';

    public const string CLOSING_ACTION = '[data-sylament-action-close]';

    public const string SMALL_LINK = '.sy-link.sy-size-sm';

    public const string BUTTON = '.sy-btn';

    public const string MEDIUM = '.sy-size-md';

    public const string TELEPORTED_DROPDOWN = '[data-sylament--support--dropdown-teleport-value="true"]';

    public const string KEPT_DROPDOWN = '[data-sylament--support--dropdown-teleport-value="false"]';

    /**
     * Gray paints an element carrying no colour class, so a gray toast is an uncoloured one.
     */
    public const string GRAY = ':not(.sy-color)';

    public static function color(string $name): string
    {
        return ".sy-color-{$name}";
    }

    public static function aligned(string $alignment): string
    {
        return self::STACK . ".sy-align-{$alignment}";
    }

    public static function verticallyAligned(string $alignment): string
    {
        return self::STACK . ".sy-vertical-align-{$alignment}";
    }
}
