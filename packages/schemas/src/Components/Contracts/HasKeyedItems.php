<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Contracts;

/**
 * A component that maps its record path as an ordered list of keyed items — the
 * state key of an item is its identity, while the record holds the same items as
 * a positional collection. The pairing is what lets a record-side violation path
 * (`sections[2].heading`) be spoken back in state terms.
 */
interface HasKeyedItems
{
    /**
     * This component's item keys in the order it writes the items onto the
     * record, so the nth key names the item the record's nth element was mapped
     * from.
     *
     * @return list<string>
     */
    public function getItemKeys(): array;
}
