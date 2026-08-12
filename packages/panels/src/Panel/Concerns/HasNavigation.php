<?php

declare(strict_types=1);

namespace Sylament\Panels\Panel\Concerns;

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;

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
}
