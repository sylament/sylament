<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Colors\Maps;

use LogicException;
use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\Maps\ButtonComponentColorMap;

it('resolves every background and text slot', function (): void {
    $map = ButtonComponentColorMap::make(Color::Blue)
        ->minContrastRatio(Color::WCAG_AA_TEXT)
        ->lightBackground(bg: 600, hover: 500)
        ->lightBackground(bg: 400, hover: 300, alternateHover: 500)
        ->darkBackground(bg: 600, hover: 500, alternateHover: 700)
        ->get();

    expect($map)->toHaveKeys([
        'bg', 'hover:bg', 'dark:bg', 'dark:hover:bg',
        'text', 'hover:text', 'dark:text', 'dark:hover:text',
    ]);
});

it('picks white text on a vibrant background', function (): void {
    $map = ButtonComponentColorMap::make(Color::Blue)
        ->lightBackground(bg: 600, hover: 500)
        ->darkBackground(bg: 600, hover: 500)
        ->get();

    // Shade 0 is white — no blue shade is readable on blue-600.
    expect($map['text'])->toBe(0);
});

it('picks a dark text shade on a pale background', function (): void {
    $map = ButtonComponentColorMap::make(Color::Yellow)
        ->lightBackground(bg: 400, hover: 300, alternateHover: 500)
        ->darkBackground(bg: 400, hover: 300)
        ->get();

    expect($map['text'])->toBeGreaterThanOrEqual(800);
});

it('requires at least one light background candidate', function (): void {
    expect(fn (): array => ButtonComponentColorMap::make(Color::Blue)->darkBackground(bg: 600, hover: 500)->get())
        ->toThrow(LogicException::class, 'lightBackground()');
});

it('requires at least one dark background candidate', function (): void {
    expect(fn (): array => ButtonComponentColorMap::make(Color::Blue)->lightBackground(bg: 600, hover: 500)->get())
        ->toThrow(LogicException::class, 'darkBackground()');
});
