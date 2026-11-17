<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

/**
 * The bare marker every action trigger carries beside its support primitive's look, and
 * the click wiring the trigger templates emit: the live action binding with the live
 * action and parameters it sends, the modal's client-side dismiss, and the browser event
 * a dispatching trigger names, by the `data-` hooks the controllers read.
 */
final class Selectors
{
    public const string TRIGGER = '.sy-ac';

    public const string LIVE_CLICK = '[data-action~="live#action"]';

    public const string DISMISS = '[data-action~="sylament--support--modal#dismiss"]';

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
}
