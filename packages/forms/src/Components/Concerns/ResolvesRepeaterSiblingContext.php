<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Sylament\Forms\State\RepeaterSiblingContext;

use function Sylament\Support\wrap;

trait ResolvesRepeaterSiblingContext
{
    /**
     * The sibling facts of {@see RepeaterSiblingContext}, or null for a field that
     * sits outside a repeater item or whose item is the only one.
     *
     * The field's path *within* an item is taken by stripping the item schema's own
     * prefix rather than by string surgery on the first dot — an item key may hold
     * one, and a layout component between the two contributes no segment either way.
     */
    public function resolveRepeaterSiblingContext(): ?RepeaterSiblingContext
    {
        $repeater = $this->getParentRepeater();
        $item = $this->getParentRepeaterItem();
        $statePath = $this->getStatePath();

        if ($repeater === null || $item === null || $statePath === null) {
            return null;
        }

        $itemPath = (string) $item->getStatePath();
        $itemKey = (string) $item->getStatePath(isAbsolute: false);

        $siblingKeys = array_keys(array_diff_key(wrap($repeater->getRawState()), [$itemKey => true]));

        if ($siblingKeys === []) {
            return null;
        }

        return new RepeaterSiblingContext(
            repeater: $repeater,
            itemKey: $itemKey,
            fieldPath: mb_substr($statePath, mb_strlen($itemPath) + 1),
            itemsPrefix: mb_substr($itemPath, 0, mb_strlen($itemPath) - mb_strlen($itemKey)),
            siblingKeys: $siblingKeys,
        );
    }
}
