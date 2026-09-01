<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Pages;

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Enums\SubNavigationPosition;
use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class SubNavigationProbePage extends Page
{
    /**
     * Redeclared rather than inherited: `static::$subNavigationPosition = …` would
     * otherwise write the base class's property and set the position for every page.
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
