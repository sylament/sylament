<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Components;

use LogicException;
use Sylament\Schemas\Schema;

/**
 * The keyed-item owner with one child schema per item key, so `CanBeRepeated` can
 * be exercised from inside an item without the forms `Repeater`.
 */
final class KeyedItemsContainer extends KeyedItemsOwner
{
    /**
     * @return array<array-key, Schema>
     */
    public function getDefaultChildSchemas(): array
    {
        $schemas = [];

        foreach ($this->getItemKeys() as $itemKey) {
            $schemas[$itemKey] = $this->getChildSchema()?->statePath($itemKey)->key($itemKey)->getClone()
                ?? throw new LogicException('The container declares no item schema.');
        }

        return $schemas;
    }
}
