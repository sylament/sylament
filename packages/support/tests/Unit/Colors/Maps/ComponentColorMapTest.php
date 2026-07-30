<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Colors\Maps;

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\Maps\ComponentColorMap;

it('resolves a slot to the lightest shade clearing the ratio', function (): void {
    $amber = Color::Amber;

    $map = ComponentColorMap::make($amber)
        ->slot('text', surface: $amber[50], fallback: 900)
        ->get();

    expect($map)->toBe(['text' => 700]);
});

it('keeps a shade that already clears the ratio', function (): void {
    $blue = Color::Blue;

    $map = ComponentColorMap::make($blue)
        ->slot('text', surface: $blue[50], fallback: 900)
        ->get();

    expect($map['text'])->toBe(600);
});

it('falls back when no shade clears the ratio', function (): void {
    $map = ComponentColorMap::make([500 => '#ffffff'])
        ->slot('text', surface: '#ffffff', fallback: 900)
        ->get();

    expect($map)->toBe(['text' => 900]);
});

it('accumulates several slots', function (): void {
    $blue = Color::Blue;

    $map = ComponentColorMap::make($blue)
        ->slot('text', surface: $blue[50])
        ->slot('dark:text', surface: $blue[900], maxShade: 500, shouldStartFromDarkest: true, fallback: 200)
        ->get();

    expect($map)->toHaveKeys(['text', 'dark:text']);
});
