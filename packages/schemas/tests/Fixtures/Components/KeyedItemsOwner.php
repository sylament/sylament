<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Components;

use Sylament\Schemas\Components\Contracts\HasKeyedItems;

/**
 * A hand-built stand-in for the forms Repeater's item keying: a subtree owner
 * whose item-key order is set rather than derived, so the translator's index
 * arm can be exercised without the forms package.
 */
final class KeyedItemsOwner extends StateField implements HasKeyedItems
{
    public function ownsRecordSubtree(): bool
    {
        return true;
    }

    /** @var list<string> */
    private array $itemKeys = [];

    /**
     * @param list<string> $itemKeys
     */
    public function itemKeys(array $itemKeys): static
    {
        $this->itemKeys = $itemKeys;

        return $this;
    }

    public function getItemKeys(): array
    {
        return $this->itemKeys;
    }
}
