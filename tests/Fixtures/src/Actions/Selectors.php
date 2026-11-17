<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

/**
 * The bare marker every action trigger carries beside its support primitive's look, and
 * the click wiring the trigger templates emit: the live action binding with the live
 * action and parameters it sends, the modal's client-side dismiss and the marker of a
 * closing trigger, and the browser event a dispatching trigger names, by the `data-`
 * hooks the controllers read; a group's inline row by its layout class, and the
 * bulk-only marker the table controller reads off a bulk group's dropdown.
 *
 * A mounted action's dialog, by the frame index its host-scoped id ends in, and an open
 * one, by the `open` attribute the host's modal renderer sets on the frames it shows; the
 * window and the footer of support's modal an action opens, by the `sy-` classes the
 * stylesheet lays them out by.
 */
final class Selectors
{
    public const string TRIGGER = '.sy-ac';

    public const string LIVE_CLICK = '[data-action~="live#action"]';

    public const string DISMISS = '[data-action~="sylament--support--modal#dismiss"]';

    public const string CLOSE_MARKER = '[data-sylament-action-close]';

    public const string INLINE_GROUP = '.sy-ac-group';

    public const string BULK_ONLY = '[data-sy-table-bulk-only]';

    public const string MOUNTED_MODAL = 'dialog[id^="sy-"][id*="-action-"]';

    public const string OPEN = '[open]';

    public const string MODAL_WINDOW = '.sy-modal-window';

    public const string MODAL_FOOTER = '.sy-modal-footer';

    /**
     * A parameter the live action binding sends, by its name and the value it carries.
     */
    public static function liveParameter(string $name, string $value): string
    {
        return "[data-live-{$name}-param='{$value}']";
    }

    public static function dispatchedEvent(string $event): string
    {
        return "[data-sylament-action-event=\"{$event}\"]";
    }

    public static function mountedModal(int $index): string
    {
        return self::MOUNTED_MODAL . "[id\$=\"-action-{$index}\"]";
    }
}
