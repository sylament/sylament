<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Colors\Components;

use Sylament\Schemas\Colors\Components\TextComponent;
use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Text sits on the page surface rather than on a tinted background of its own, so a light
 * palette is as unreadable here as anywhere: every shipped palette must resolve to a shade
 * clearing AA against gray-50 in light mode and gray-700 in dark mode.
 */
dataset('shipped palettes', array_keys(ColorManager::DEFAULT_COLORS));

function textComponent(): TextComponent
{
    return new TextComponent(new ColorManager(new ContainerBuilder()));
}

it('resolves text clearing WCAG AA against the light page surface', function (string $palette): void {
    $shades = ColorManager::DEFAULT_COLORS[$palette];
    $gray = ColorManager::DEFAULT_COLORS['gray'];

    $map = textComponent()->getColorMap($shades);

    expect(Color::calculateContrastRatio($gray[50], $shades[$map['text']]))
        ->toBeGreaterThanOrEqual(Color::WCAG_AA_TEXT);
})->with('shipped palettes');

it('resolves text clearing WCAG AA against the dark page surface', function (string $palette): void {
    $shades = ColorManager::DEFAULT_COLORS[$palette];
    $gray = ColorManager::DEFAULT_COLORS['gray'];

    $map = textComponent()->getColorMap($shades);

    expect(Color::calculateContrastRatio($gray[700], $shades[$map['dark:text']]))
        ->toBeGreaterThanOrEqual(Color::WCAG_AA_TEXT);
})->with('shipped palettes');
