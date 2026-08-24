<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use LogicException;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Contracts\HasKeyedItems;
use Sylament\Schemas\Schema;

trait CanBeRepeated
{
    /**
     * The memoised answer: the item schema this component sits in, or `false`
     * once the walk has proven there is none.
     */
    protected Schema|false|null $cachedParentRepeaterItem = null;

    /**
     * The nearest ancestor that holds keyed items — a `Repeater`, or whatever else
     * implements the contract — or null outside one.
     */
    public function getParentRepeater(): (Component&HasKeyedItems)|null
    {
        $repeater = $this->getParentRepeaterItem()?->getParentComponent();

        if ($repeater === null) {
            return null;
        }

        if (!$repeater instanceof HasKeyedItems) {
            throw new LogicException(sprintf('The parent of a repeater item must hold keyed items, [%s] does not.', $repeater::class));
        }

        return $repeater;
    }

    public function getParentRepeaterItem(): ?Schema
    {
        if ($this->cachedParentRepeaterItem !== null) {
            return $this->cachedParentRepeaterItem instanceof Schema ? $this->cachedParentRepeaterItem : null;
        }

        $container = $this->getContainer();
        $parentComponent = $container->getParentComponent();

        if ($parentComponent === null) {
            $this->cachedParentRepeaterItem = false;
        } elseif ($parentComponent instanceof HasKeyedItems) {
            $this->cachedParentRepeaterItem = $container;
        } else {
            $this->cachedParentRepeaterItem = $parentComponent->getParentRepeaterItem() ?? false;
        }

        return $this->cachedParentRepeaterItem instanceof Schema ? $this->cachedParentRepeaterItem : null;
    }

    public function getParentRepeaterItemIndex(): int
    {
        $item = $this->getParentRepeaterItem();
        $repeater = $this->getParentRepeater();

        if ($item === null || $repeater === null) {
            return 0;
        }

        $index = array_search($item->getStatePath(isAbsolute: false), array_values($repeater->getItemKeys()), true);

        return $index === false ? 0 : $index;
    }
}
