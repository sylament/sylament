<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\Components\BadgeComponent;

it('resolves badge text readable on its own lightest shade and, in dark mode, on gray 600, for every catalog palette', function (): void {
    $policy = $this::getContainer()->get(BadgeComponent::class);
    $failures = [];

    foreach (Color::all() as $name => $palette) {
        $map = $policy->getColorMap($palette);

        foreach (['text' => $palette[50], 'dark:text' => Color::Zinc[600]] as $slot => $surface) {
            if (Color::calculateContrastRatio($palette[$map[$slot]], $surface) < 4.5) {
                $failures[] = "{$name} {$slot}";
            }
        }
    }

    expect($failures)->toBe([]);
});
