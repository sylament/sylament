<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\Maps\IconButtonComponentColorMap;

it('resolves the icon to the first shade meeting the ratio on each surface, its hover one shade away', function (): void {
    expect(IconButtonComponentColorMap::make(Color::Indigo)
        ->lightSurface(Color::Zinc[50])
        ->darkSurface(Color::Zinc[700])
        ->get())->toBe(['text' => 600, 'hover:text' => 500, 'dark:text' => 400, 'dark:hover:text' => 300]);
});

it('refuses to resolve before a light and a dark surface are configured', function (): void {
    expect(fn (): array => IconButtonComponentColorMap::make(Color::Indigo)->darkSurface(Color::Zinc[700])->get())
        ->toThrow(LogicException::class, 'A `lightSurface()` must be configured before calling `get()`.')
        ->and(fn (): array => IconButtonComponentColorMap::make(Color::Indigo)->lightSurface(Color::Zinc[50])->get())
        ->toThrow(LogicException::class, 'A `darkSurface()` must be configured before calling `get()`.');
});
