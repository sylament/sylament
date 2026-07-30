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

it('darkens rather than lightens on hover for shades above 500', function (): void {
    $gray = Color::Zinc;

    $map = IconButtonComponentColorMap::make(Color::Yellow)
        ->lightSurface($gray[50])
        ->darkSurface($gray[700])
        ->get();

    expect($map['hover:text'])->toBeGreaterThan($map['text'] - 200);
});

it('requires a light surface', function (): void {
    expect(fn (): array => IconButtonComponentColorMap::make(Color::Blue)->darkSurface('#000')->get())
        ->toThrow(LogicException::class, 'lightSurface()');
});

it('requires a dark surface', function (): void {
    expect(fn (): array => IconButtonComponentColorMap::make(Color::Blue)->lightSurface('#fff')->get())
        ->toThrow(LogicException::class, 'darkSurface()');
});
