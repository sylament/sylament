<?php

declare(strict_types=1);

namespace Sylament\Panels\Pages;

use BackedEnum;
use Closure;
use ReflectionClass;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Panel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Twig\Markup;

use function Symfony\Component\String\u;

abstract class Page
{
    use DefaultActionTrait;

    protected static ?string $title = null;

    protected static ?string $slug = null;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|BackedEnum|null $activeNavigationIcon = null;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = null;

    protected static ?string $navigationGroup = null;

    protected ?string $template = null;

    protected ?string $heading = null;

    protected ?string $subheading = null;

    public function getTitle(): string|Markup
    {
        return static::$title ?? static::getHumanizedClassBasename();
    }

    public function getHeading(): string|Markup|null
    {
        return $this->heading ?? $this->getTitle();
    }

    public function getSubheading(): string|Markup|null
    {
        return $this->subheading;
    }

    public function getTemplate(): string
    {
        return $this->template ?? '@SylamentPanels/partials/Page/index.html.twig';
    }

    public static function getSlug(): string
    {
        return static::$slug ?? u(new ReflectionClass(static::class)->getShortName())->kebab()->toString();
    }

    public static function getRoutePath(): string
    {
        return '/' . static::getSlug();
    }

    public static function getRouteName(Panel $panel): string
    {
        return sprintf('sylament.%s.pages.%s', $panel->getId(), str_replace('/', '.', static::getSlug()));
    }

    public static function getComponentName(): string
    {
        return str_replace('\\', ':', static::class);
    }

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? static::$title ?? static::getHumanizedClassBasename();
    }

    public static function getNavigationIcon(): string|BackedEnum|Closure|null
    {
        return static::$navigationIcon;
    }

    public static function getActiveNavigationIcon(): string|BackedEnum|Closure|null
    {
        return static::$activeNavigationIcon ?? static::getNavigationIcon();
    }

    public static function getNavigationSort(): ?int
    {
        return static::$navigationSort;
    }

    public static function getNavigationGroup(): ?string
    {
        return static::$navigationGroup;
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    /**
     * @return string|array<int|string, string>|null
     */
    public static function getNavigationBadgeColor(): string|array|null
    {
        return null;
    }

    /**
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(Panel $panel): array
    {
        $routeName = static::getRouteName($panel);

        return [
            NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->sort(static::getNavigationSort())
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->url(fn (UrlGeneratorInterface $urlGenerator): string => $urlGenerator->generate($routeName))
                ->isActiveWhen(fn (RequestStack $requestStack): bool => $requestStack->getCurrentRequest()?->attributes->get('_route') === $routeName),
        ];
    }

    protected static function getHumanizedClassBasename(): string
    {
        return u(new ReflectionClass(static::class)->getShortName())->kebab()->replace('-', ' ')->title(allWords: true)->toString();
    }
}
