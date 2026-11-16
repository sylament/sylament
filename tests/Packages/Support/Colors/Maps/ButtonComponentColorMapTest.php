<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\Maps\ButtonComponentColorMap;

it('takes the first background candidate that carries light text, else the last one with dark text', function (array $palette, array $map): void {
    expect(ButtonComponentColorMap::make($palette)
        ->lightBackground(bg: 600, hover: 500)
        ->lightBackground(bg: 400, hover: 300, alternateHover: 500)
        ->darkBackground(bg: 600, hover: 500, alternateHover: 700)
        ->get())->toBe($map);
})->with([
    'a deep palette, in white' => [Color::Indigo, [
        'bg' => 600, 'hover:bg' => 500, 'dark:bg' => 600, 'dark:hover:bg' => 500,
        'text' => 0, 'hover:text' => 0, 'dark:text' => 0, 'dark:hover:text' => 0,
    ]],
    'a pale palette, in its own dark shades' => [Color::Amber, [
        'bg' => 400, 'hover:bg' => 300, 'dark:bg' => 600, 'dark:hover:bg' => 500,
        'text' => 900, 'hover:text' => 800, 'dark:text' => 950, 'dark:hover:text' => 950,
    ]],
]);

it('refuses to resolve before a light and a dark background candidate are configured', function (): void {
    expect(fn (): array => ButtonComponentColorMap::make(Color::Indigo)->darkBackground(bg: 600, hover: 500)->get())
        ->toThrow(LogicException::class, 'At least one `lightBackground()` candidate must be configured before calling `get()`.')
        ->and(fn (): array => ButtonComponentColorMap::make(Color::Indigo)->lightBackground(bg: 600, hover: 500)->get())
        ->toThrow(LogicException::class, 'At least one `darkBackground()` candidate must be configured before calling `get()`.');
});
