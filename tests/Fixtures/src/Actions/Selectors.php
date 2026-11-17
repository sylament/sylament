<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

/**
 * The markup actions add to support's primitives: the bare marker every action trigger
 * carries beside its look, one trigger by the action name its live parameters mount, a
 * group's inline row by its layout class, and the bulk-only marker a bulk trigger and a bulk
 * group's dropdown carry; the click wiring the trigger templates emit, by the `data-` hooks
 * the controllers read: the live action binding and a parameter it sends, the modal's
 * client-side dismiss, the marker of a closing trigger and the browser event a dispatching
 * trigger names; a mounted action's dialog, by the frame index its host-scoped id ends in,
 * and an open one, by the `open` attribute the host's modal renderer sets on the frames it
 * shows; the list of a record's own violations an action modal shows above its form, and each
 * one in it; and the record select an attach or associate modal opens, with its label.
 */
final class Selectors
{
    public const string TRIGGER = '.sy-ac';

    public const string INLINE_GROUP = '.sy-ac-group';

    public const string BULK_ONLY = '[data-sy-table-bulk-only]';

    public const string LIVE_CLICK = '[data-action~="live#action"]';

    public const string DISMISS = '[data-action~="sylament--support--modal#dismiss"]';

    public const string CLOSE_MARKER = '[data-sylament-action-close]';

    public const string MOUNTED_MODAL = 'dialog[id^="sy-"][id*="-action-"]';

    public const string OPEN = '[open]';

    public const string RECORD_ERRORS = '[data-record-errors]';

    public const string RECORD_ERROR = self::RECORD_ERRORS . ' li';

    public const string RECORD_SELECT = SupportSelectors::OPEN_MODAL . ' select[name="recordId"]';

    public const string RECORD_SELECT_LABEL = SupportSelectors::OPEN_MODAL . ' label[for="mountedActionSchema0.recordId"]';

    public static function trigger(string $name): string
    {
        return "button[data-live-action-param=\"mountAction\"][data-live-name-param=\"{$name}\"]";
    }

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
