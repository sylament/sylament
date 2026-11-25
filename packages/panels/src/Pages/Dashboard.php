<?php

declare(strict_types=1);

namespace Sylament\Panels\Pages;

use BackedEnum;
use Closure;
use Sylament\Panels\Twig\PanelsIconAlias;
use Sylament\Support\Icons\IconManager;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Twig\Markup;

#[AsLiveComponent]
class Dashboard extends Page
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    public static function getRoutePath(): string
    {
        return static::$routePath;
    }

    public function getTitle(): string|Markup
    {
        return static::$title ?? new TranslatableMessage('pages.dashboard.title', [], 'sylament-panels')->trans($this->getTranslator());
    }

    public static function getNavigationLabel(): string|TranslatableInterface
    {
        return static::$navigationLabel ?? static::$title ?? new TranslatableMessage('pages.dashboard.title', [], 'sylament-panels');
    }

    public static function getNavigationIcon(): string|BackedEnum|Markup|Closure|null
    {
        return static::$navigationIcon
            ?? static fn (IconManager $icons): string|BackedEnum|Markup => $icons->resolve(PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM) ?? 'heroicons:home';
    }
}
