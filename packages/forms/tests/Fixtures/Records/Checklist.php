<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Records;

/**
 * The record shape a repeater maps onto: `tags` is the nullable JSON-column
 * plain array — the target a `nullWhenBlank()` repeater can write null to — and
 * `steps` the non-nullable variant the branch pass refuses that on. Both are
 * plain arrays with no declared element type, which is what separates them from
 * the object collection {@see TaggedPost} declares.
 */
final class Checklist
{
    /**
     * @var array<array-key, mixed>|null
     */
    public ?array $tags = null;

    /**
     * @var array<array-key, mixed>
     */
    public array $steps = [];
}
