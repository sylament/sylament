<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Colors\Components;

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Components\BadgeComponent;
use Sylament\Support\Colors\Components\ButtonComponent;
use Sylament\Support\Colors\Components\LinkComponent;
use Sylament\Support\Colors\Components\OutlinedButtonComponent;

use function Sylament\Support\Tests\policy;

dataset('shipped palettes', array_keys(ColorManager::DEFAULT_COLORS));

it('resolves badge text that clears WCAG AA against the badge background', function (string $palette): void {
    // `badge.css` hardcodes `text-color-600`, which measures 3.06:1 for warning and 3.03:1
    // for success — this is the test that fails if that shade comes back.
    $shades = ColorManager::DEFAULT_COLORS[$palette];

    $map = policy(BadgeComponent::class)->getColorMap($shades);

    expect(Color::calculateContrastRatio($shades[50], $shades[$map['text']]))
        ->toBeGreaterThanOrEqual(Color::WCAG_AA_TEXT);
})->with('shipped palettes');

it('resolves link text that clears WCAG AA against the page surface', function (string $palette): void {
    $shades = ColorManager::DEFAULT_COLORS[$palette];
    $gray = ColorManager::DEFAULT_COLORS['gray'];

    $map = policy(LinkComponent::class)->getColorMap($shades);

    expect(Color::calculateContrastRatio($gray[50], $shades[$map['text']]))
        ->toBeGreaterThanOrEqual(Color::WCAG_AA_TEXT);
})->with('shipped palettes');

it('resolves filled-button text that clears WCAG AA against the chosen background', function (string $palette): void {
    // `button.css` paints `bg-color-600 text-white`, which measures 3.17:1 for both warning
    // and success. The two-pass map must pick a dark text shade for pale palettes.
    $shades = ColorManager::DEFAULT_COLORS[$palette];

    $map = policy(ButtonComponent::class)->getColorMap($shades);

    $text = $map['text'] === 0 ? 'oklch(1 0 0)' : $shades[$map['text']];

    expect(Color::calculateContrastRatio($shades[$map['bg']], $text))
        ->toBeGreaterThanOrEqual(Color::WCAG_AA_TEXT);
})->with('shipped palettes');

it('resolves outlined-button text that clears WCAG AA against the page surface', function (string $palette): void {
    // The outlined variant paints `text-color-600` over a transparent background, which
    // measures 3.05:1 for warning and 3.04:1 for success against gray-50.
    $shades = ColorManager::DEFAULT_COLORS[$palette];
    $gray = ColorManager::DEFAULT_COLORS['gray'];

    $map = policy(OutlinedButtonComponent::class)->getColorMap($shades);

    expect(Color::calculateContrastRatio($gray[50], $shades[$map['text']]))
        ->toBeGreaterThanOrEqual(Color::WCAG_AA_TEXT);
})->with('shipped palettes');
