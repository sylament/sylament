<?php

declare(strict_types=1);

use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Panel;

it('claims the panel root with a negative sort and a stable title', function (): void {
    expect(Dashboard::getRoutePath())->toBe('/')
        ->and(Dashboard::getSlug())->toBe('dashboard')
        ->and(Dashboard::getRouteName(Panel::make()->id('admin')))->toBe('sylament.admin.pages.dashboard')
        ->and(Dashboard::getNavigationSort())->toBe(-2)
        ->and(new Dashboard()->getTitle())->toBe('Dashboard');
});

it('resolves its navigation icon through the icon alias with a heroicon default', function (): void {
    expect(Dashboard::getNavigationIcon())->toBeInstanceOf(Closure::class);
});
