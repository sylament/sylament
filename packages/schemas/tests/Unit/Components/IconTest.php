<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use Sylament\Schemas\Components\Icon;
use Sylament\Support\Enum\IconSize;

it('returns the icon', function (): void {
    expect(Icon::make('heroicon-o-star')->getIcon())->toBe('heroicon-o-star');
});

it('evaluates an icon closure', function (): void {
    expect(Icon::make(fn (): string => 'heroicon-o-eye')->getIcon())->toBe('heroicon-o-eye');
});

it('passes the icon component to an icon closure as $component', function (): void {
    expect(Icon::make(fn (Icon $component): string => $component::class)->getIcon())->toBe(Icon::class);
});

it('normalizes a known size string to the IconSize enum', function (): void {
    expect(Icon::make('x')->size('lg')->getSize())->toBe(IconSize::Large);
});

it('keeps an IconSize enum size', function (): void {
    expect(Icon::make('x')->size(IconSize::Small)->getSize())->toBe(IconSize::Small);
});

it('keeps an unknown size string verbatim', function (): void {
    expect(Icon::make('x')->size('huge')->getSize())->toBe('huge');
});

it('returns null for a blank size', function (): void {
    expect(Icon::make('x')->getSize())->toBeNull()
        ->and(Icon::make('x')->size('')->getSize())->toBeNull();
});

it('round-trips the color', function (): void {
    expect(Icon::make('x')->color('primary')->getColor())->toBe('primary')
        ->and(Icon::make('x')->getColor())->toBeNull()
        ->and(Icon::make('x')->hasColor())->toBeFalse()
        ->and(Icon::make('x')->color('danger')->hasColor())->toBeTrue();
});

it('falls back to the default color', function (): void {
    expect(Icon::make('x')->defaultColor('gray')->getColor())->toBe('gray')
        ->and(Icon::make('x')->color('primary')->defaultColor('gray')->getColor())->toBe('primary');
});

it('round-trips the tooltip', function (): void {
    expect(Icon::make('x')->tooltip('More info')->getTooltip())->toBe('More info')
        ->and(Icon::make('x')->getTooltip())->toBeNull()
        ->and(Icon::make('x')->hasTooltip())->toBeFalse()
        ->and(Icon::make('x')->tooltip('t')->hasTooltip())->toBeTrue();
});

it('evaluates a tooltip closure', function (): void {
    expect(Icon::make('x')->tooltip(fn (): string => 'Lazy')->getTooltip())->toBe('Lazy');
});

it('is visible by default and can be hidden', function (): void {
    expect(Icon::make('x')->isVisible())->toBeTrue()
        ->and(Icon::make('x')->hidden()->isVisible())->toBeFalse();
});
