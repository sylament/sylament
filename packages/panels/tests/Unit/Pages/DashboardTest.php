<?php

declare(strict_types=1);

use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Panel;
use Sylament\Support\Evaluation\Invoker;
use Sylament\Support\Icons\IconManager;
use Symfony\Component\DependencyInjection\Container;

it('claims the panel root with a negative sort and a stable title', function (): void {
    expect(Dashboard::getRoutePath())->toBe('/')
        ->and(Dashboard::getSlug())->toBe('dashboard')
        ->and(Dashboard::getRouteName(Panel::make()->id('admin')))->toBe('sylament.admin.pages.dashboard')
        ->and(Dashboard::getNavigationSort())->toBe(-2)
        ->and(new Dashboard()->getTitle())->toBe('Dashboard');
});

it('resolves its navigation icon through the icon alias with a heroicon default', function (): void {
    $icon = Dashboard::getNavigationIcon();

    expect($icon)->toBeInstanceOf(Closure::class);

    $invoker = new Invoker(new Container());
    $icons = new IconManager();

    expect($invoker->call($icon, typedInjections: [IconManager::class => $icons]))->toBe('heroicons:home');

    $icons->register(['pages.dashboard.navigation-item' => 'custom:dashboard']);

    expect($invoker->call($icon, typedInjections: [IconManager::class => $icons]))->toBe('custom:dashboard');
});

it('resolves the navigation icon closure from the container when nothing is injected', function (): void {
    $icon = Dashboard::getNavigationIcon();

    $icons = new IconManager();
    $icons->register(['pages.dashboard.navigation-item' => 'custom:dashboard']);

    $container = new Container();
    $container->set(IconManager::class, $icons);

    expect(new Invoker($container)->call($icon))->toBe('custom:dashboard');
});
