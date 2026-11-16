<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Schemas\Components\Contracts\HasKeyedItems;
use Sylament\Schemas\Components\Group;

/**
 * A layout component that keys items without being a field, the shape of an
 * application's own keyed-items component; it keys none.
 */
class KeyedItemsGroup extends Group implements HasKeyedItems
{
    public function getItemKeys(): array
    {
        return [];
    }

    public function getItemStatePaths(): array
    {
        return [];
    }
}
