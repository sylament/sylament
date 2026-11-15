<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\Components\IconButtonComponent;

it('resolves the icon and its hover at 3:1 on gray 50 and, in dark mode, on gray 700, for every catalog palette', function (): void {
    $policy = $this::getContainer()->get(IconButtonComponent::class);
    $failures = [];

    foreach (Color::all() as $name => $palette) {
        $map = $policy->getColorMap($palette);
        $surfaces = [
            'text' => Color::Zinc[50],
            'hover:text' => Color::Zinc[50],
            'dark:text' => Color::Zinc[700],
            'dark:hover:text' => Color::Zinc[700],
        ];

        foreach ($surfaces as $slot => $surface) {
            if (Color::calculateContrastRatio($palette[$map[$slot]], $surface) < 3.0) {
                $failures[] = "{$name} {$slot}";
            }
        }
    }

    expect($failures)->toBe([]);
});
