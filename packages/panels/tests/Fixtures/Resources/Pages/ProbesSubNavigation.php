<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Enums\SubNavigationPosition;

/**
 * Turns a resource page's sub-navigation on for one render, so the band block each
 * resource partial carries is exercised in its non-empty state.
 */
trait ProbesSubNavigation
{
    /**
     * Redeclared rather than inherited: `static::$subNavigationPosition = …` would
     * otherwise write the base page's property and set the position for every page.
     */
    protected static ?SubNavigationPosition $subNavigationPosition = null;

    /**
     * @var array<NavigationItem|NavigationGroup>
     */
    private static array $probeSubNavigation = [];

    /**
     * @param array<NavigationItem|NavigationGroup> $subNavigation
     */
    public static function probe(?SubNavigationPosition $position, array $subNavigation = []): void
    {
        self::$subNavigationPosition = $position;
        self::$probeSubNavigation = $subNavigation;
    }

    /**
     * @return array<NavigationItem|NavigationGroup>
     */
    public function getSubNavigation(): array
    {
        return self::$probeSubNavigation;
    }
}
