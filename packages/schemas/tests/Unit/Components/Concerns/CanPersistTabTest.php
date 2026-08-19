<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Components\Tabs;

it('does not persist the active tab by default', function (): void {
    expect(Tabs::make('Article')->isTabPersisted())->toBeFalse();
});

it('persists the active tab when asked', function (): void {
    expect(Tabs::make('Article')->persistTab()->isTabPersisted())->toBeTrue();
});

it('takes a false condition back off', function (): void {
    expect(Tabs::make('Article')->persistTab()->persistTab(false)->isTabPersisted())->toBeFalse();
});

it('evaluates a persistence closure', function (): void {
    expect(Tabs::make('Article')->persistTab(fn (): bool => true)->isTabPersisted())->toBeTrue();
});
