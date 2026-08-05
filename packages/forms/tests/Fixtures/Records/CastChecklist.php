<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Records;

/**
 * The record shape a value-object repeater maps onto: `lines` is the
 * docblock-typed object list an `itemsAs()` repeater fills from and writes
 * back, `groups` the plain nested arm beside it, and `objectGroups` the nested
 * arm whose outer items are objects too — so one record carries every nesting
 * quadrant beside the flat cast and uncast collections.
 */
final class CastChecklist
{
    /**
     * @var list<LineItem>|null
     */
    public ?array $lines = null;

    /**
     * @var list<array<string, mixed>>|null
     */
    public ?array $groups = null;

    /**
     * @var list<GroupItem>|null
     */
    public ?array $objectGroups = null;
}
