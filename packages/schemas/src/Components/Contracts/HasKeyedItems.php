<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Contracts;

/**
 * A component that maps its record path as a set of keyed items — the state key
 * of an item is its identity, while the record holds the same items in a
 * collection of its own keying. The pairing is what lets a record-side violation
 * path (`sections[2].heading`) be spoken back in state terms.
 */
interface HasKeyedItems
{
    /**
     * This component's item keys, keyed by the key the record's collection holds
     * each item under — a violation path indexes the record, so only the record's
     * own keying can name the item a violation belongs to.
     *
     * @return array<int|string, string>
     */
    public function getItemKeys(): array;

    /**
     * The dotted state paths one item declares, relative to that item — the only
     * paths a violation inside an item can be spoken in, since they are exactly
     * what the component wrote onto the item's record.
     *
     * @return list<string>
     */
    public function getItemStatePaths(): array;
}
