<?php

declare(strict_types=1);

namespace Sylament\Panels\Pages;

use BackedEnum;
use Closure;
use Sylament\Panels\Twig\PanelsIconAlias;
use Sylament\Support\Icons\IconManager;
use Twig\Markup;

class Dashboard extends Page
{
    protected static ?string $title = 'Dashboard';

    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    public static function getRoutePath(): string
    {
        return static::$routePath;
    }

    public static function getNavigationIcon(): string|BackedEnum|Markup|Closure|null
    {
        return static::$navigationIcon
            ?? static fn (IconManager $icons): string|BackedEnum|Markup => $icons->resolve(PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM) ?? 'heroicons:home';
    }
}
