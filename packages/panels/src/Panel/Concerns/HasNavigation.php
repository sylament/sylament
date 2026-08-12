<?php

declare(strict_types=1);

namespace Sylament\Panels\Panel\Concerns;

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Page;

trait HasNavigation
{
    /**
     * @var array<string|int, NavigationGroup|string>
     */
    protected array $navigationGroups = [];

    /**
     * @var array<NavigationItem>
     */
    protected array $navigationItems = [];

    /**
     * @param array<string|int, NavigationGroup|string> $groups
     */
    public function navigationGroups(array $groups): static
    {
        $this->navigationGroups = [...$this->navigationGroups, ...$groups];

        return $this;
    }

    /**
     * @param array<NavigationItem> $items
     */
    public function navigationItems(array $items): static
    {
        $this->navigationItems = [...$this->navigationItems, ...$items];

        return $this;
    }

    /**
     * @return array<string|int, NavigationGroup|string>
     */
    public function getNavigationGroups(): array
    {
        return $this->navigationGroups;
    }

    /**
     * @return array<NavigationItem>
     */
    public function getNavigationItems(): array
    {
        return $this->navigationItems;
    }

    /**
     * @return array<NavigationGroup>
     */
    public function getNavigation(): array
    {
        $items = [];

        foreach ($this->getPages() as $page) {
            foreach ($page::getNavigationItems($this) as $item) {
                $items[] = clone $item;
            }
        }

        foreach ($this->getNavigationItems() as $item) {
            $items[] = clone $item;
        }

        $items = array_values(array_filter($items, fn (NavigationItem $item): bool => $item->isVisible()));
        usort($items, fn (NavigationItem $a, NavigationItem $b): int => $a->getSort() <=> $b->getSort());

        $itemsByGroup = [];

        foreach ($items as $item) {
            $itemsByGroup[$item->getGroup() ?? ''][] = $item;
        }

        $groups = [];

        if (isset($itemsByGroup[''])) {
            $groups[] = NavigationGroup::make()->items($itemsByGroup['']);
            unset($itemsByGroup['']);
        }

        foreach ($this->getNavigationGroups() as $key => $registered) {
            $label = $registered instanceof NavigationGroup ? $registered->getLabel() : $registered;
            $matched = array_find([$key, $label], fn ($candidate): bool => is_string($candidate) && isset($itemsByGroup[$candidate]));

            if ($matched === null) {
                continue;
            }

            $group = $registered instanceof NavigationGroup ? clone $registered : NavigationGroup::make($registered);
            $groups[] = $group->items($itemsByGroup[$matched]);
            unset($itemsByGroup[$matched]);
        }

        foreach ($itemsByGroup as $name => $groupItems) {
            $groups[] = NavigationGroup::make((string) $name)->items($groupItems);
        }

        $collapsibleFallback = $this->hasCollapsibleNavigationGroups();

        foreach ($groups as $group) {
            $group->collapsibleFallback($collapsibleFallback);
        }

        return $groups;
    }

    /**
     * @return array<class-string<Page>>
     */
    abstract public function getPages(): array;

    abstract public function hasCollapsibleNavigationGroups(): bool;
}
