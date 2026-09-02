<?php

declare(strict_types=1);

namespace Sylament\Forms\State;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Contracts\HasKeyedItems;

/**
 * The five facts a field inside a repeater item needs to speak about the same
 * field in its siblings: the repeater holding the items, this item's key, the
 * field's path within an item, the absolute prefix every item key hangs off,
 * and the keys of the other items.
 */
final readonly class RepeaterSiblingContext
{
    /**
     * @param list<int|string> $siblingKeys
     */
    public function __construct(
        public Component&HasKeyedItems $repeater,
        public string $itemKey,
        public string $fieldPath,
        public string $itemsPrefix,
        public array $siblingKeys,
    ) {
    }
}
