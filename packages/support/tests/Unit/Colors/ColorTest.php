<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Colors;

use InvalidArgumentException;
use Sylament\Support\Colors\Color;

it('passes an oklch value through untouched', function (): void {
    expect(Color::convertToOklch('oklch(0.546 0.245 262.881)'))
        ->toBe('oklch(0.546 0.245 262.881)');
});

it('converts a hex value to oklch', function (): void {
    // #2563eb shares the blue-600 token's lightness and hue but not its chroma — the
    // token `oklch(0.546 0.245 262.881)` converts to #155dfc, not to this hex.
    expect(Color::convertToOklch('#2563eb'))->toBe('oklch(0.546 0.215 262.881)');
});

it('throws on an unparseable color rather than yielding black', function (): void {
    expect(fn (): string => Color::convertToOklch('#zzzzzz'))
        ->toThrow(InvalidArgumentException::class, 'Unable to parse the color');
});

it('calculates the contrast ratio between two colors', function (): void {
    expect(Color::calculateContrastRatio('#000000', '#ffffff'))->toBeGreaterThan(20.9)
        ->and(Color::calculateContrastRatio('#000000', '#ffffff'))->toBeLessThan(21.1);
});

it('finds the lightest shade clearing the ratio against a surface', function (): void {
    $amber = Color::Amber;

    expect(Color::findShade(palette: $amber, surface: $amber[50], minRatio: Color::WCAG_AA_TEXT))
        ->toBe(700);
});

it('finds a darker-first shade when asked to start from the darkest', function (): void {
    $amber = Color::Amber;

    expect(Color::findShade(
        palette: $amber,
        surface: 'oklch(0.37 0.013 285.805)',
        minRatio: Color::WCAG_AA_TEXT,
        maxShade: 500,
        shouldStartFromDarkest: true,
    ))->toBeInt();
});

it('returns null when no shade clears the ratio', function (): void {
    expect(Color::findShade(palette: [500 => '#ffffff'], surface: '#ffffff'))->toBeNull();
});

it('generates an eleven-shade palette from a single color', function (): void {
    $palette = Color::generatePalette('#7c3aed');

    expect($palette)->toHaveCount(11)
        ->and(array_keys($palette))->toBe([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950])
        ->and($palette[500])->toStartWith('oklch(');
});

it('drops chroma for an achromatic source color', function (): void {
    $palette = Color::generatePalette('#808080');

    expect($palette[500])->toContain(' 0 ');
});

it('reports lightness against the 0.65 threshold', function (): void {
    expect(Color::isLight('#ffffff'))->toBeTrue()
        ->and(Color::isLight('#000000'))->toBeFalse();
});

it('exposes every palette by name', function (): void {
    expect(Color::all())->toHaveCount(26)
        ->and(Color::all())->toHaveKeys(['slate', 'blue', 'amber', 'mauve', 'olive', 'mist', 'taupe']);
});
