<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Colors\Maps;

use LogicException;
use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\Maps\IconButtonComponentColorMap;

it('resolves the four icon-button text slots', function (): void {
    $gray = Color::Zinc;

    $map = IconButtonComponentColorMap::make(Color::Blue)
        ->minContrastRatio(Color::WCAG_AA_NON_TEXT)
        ->lightSurface($gray[50])
        ->darkSurface($gray[700])
        ->darkMaxShade(500)
        ->get();

    expect($map)->toHaveKeys(['text', 'hover:text', 'dark:text', 'dark:hover:text']);
});

it('darkens on hover when the readable light shade is above 500', function (): void {
    $gray = Color::Zinc;

    // Yellow is pale enough that the lightest shade readable on a light surface is 700.
    $map = IconButtonComponentColorMap::make(Color::Yellow)
        ->lightSurface($gray[50])
        ->darkSurface($gray[700])
        ->get();

    expect($map['hover:text'])->toBeGreaterThan($map['text']);
});

it('lightens on hover when the readable light shade is at or below 500', function (): void {
    $gray = Color::Zinc;

    // Blue is vibrant enough to stay readable on a light surface down to shade 500.
    $map = IconButtonComponentColorMap::make(Color::Blue)
        ->lightSurface($gray[50])
        ->darkSurface($gray[700])
        ->get();

    expect($map['hover:text'])->toBeLessThan($map['text']);
});

it('requires a light surface', function (): void {
    expect(fn (): array => IconButtonComponentColorMap::make(Color::Blue)->darkSurface('#000')->get())
        ->toThrow(LogicException::class, 'lightSurface()');
});

it('requires a dark surface', function (): void {
    expect(fn (): array => IconButtonComponentColorMap::make(Color::Blue)->lightSurface('#fff')->get())
        ->toThrow(LogicException::class, 'darkSurface()');
});
