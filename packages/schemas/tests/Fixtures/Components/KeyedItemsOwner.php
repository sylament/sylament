<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Components;

use Sylament\Schemas\Components\Contracts\HasKeyedItems;

/**
 * A hand-built stand-in for the forms Repeater's item keying: a subtree owner
 * whose item keys and item-relative state paths are set rather than derived, so
 * the translator's index arm can be exercised without the forms package.
 */
final class KeyedItemsOwner extends StateField implements HasKeyedItems
{
    public function ownsRecordSubtree(): bool
    {
        return true;
    }

    /** @var array<int|string, string> */
    private array $itemKeys = [];

    /** @var list<string> */
    private array $itemStatePaths = [];

    /**
     * @param array<int|string, string> $itemKeys
     */
    public function itemKeys(array $itemKeys): static
    {
        $this->itemKeys = $itemKeys;

        return $this;
    }

    /**
     * @param list<string> $itemStatePaths
     */
    public function itemStatePaths(array $itemStatePaths): static
    {
        $this->itemStatePaths = $itemStatePaths;

        return $this;
    }

    public function getItemKeys(): array
    {
        return $this->itemKeys;
    }

    public function getItemStatePaths(): array
    {
        return $this->itemStatePaths;
    }
}
