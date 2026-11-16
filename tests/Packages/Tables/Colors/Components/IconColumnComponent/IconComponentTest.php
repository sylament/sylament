<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Sylament\Tables\Colors\Components\IconColumnComponent\IconComponent;

it('emits the shades it resolves for a named colour, and nothing at all for gray', function (): void {
    $colors = $this::getContainer()->get(ColorManager::class);

    expect(array_diff($colors->getComponentClasses(IconComponent::class, 'primary'), ['sy-color', 'sy-color-primary']))->not->toBeEmpty()
        ->and($colors->getComponentClasses(IconComponent::class, 'gray'))->toBe([]);
});

it('resolves the icon at 3:1 on white and, in dark mode, on gray 900 for every catalog palette, falling under 4.5:1 for some', function (): void {
    $policy = $this::getContainer()->get(IconComponent::class);
    $failures = [];
    $underTextRatio = [];

    foreach (Color::all() as $name => $palette) {
        $map = $policy->getColorMap($palette);

        foreach (['text' => 'oklch(1 0 0)', 'dark:text' => Color::Zinc[900]] as $slot => $surface) {
            $ratio = Color::calculateContrastRatio($palette[$map[$slot]], $surface);

            if ($ratio < Color::WCAG_AA_NON_TEXT) {
                $failures[] = "{$name} {$slot}";
            }

            if ($ratio < Color::WCAG_AA_TEXT) {
                $underTextRatio[] = "{$name} {$slot}";
            }
        }
    }

    expect($failures)->toBe([])
        ->and($underTextRatio)->not->toBeEmpty();
});
