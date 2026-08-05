<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Records;

/**
 * The record shape a value-object repeater maps onto: `lines` is the
 * docblock-typed object list an `itemsAs()` repeater fills from and writes
 * back, and `groups` the plain nested arm beside it, so one record carries both
 * the cast and the uncast collection.
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
}
