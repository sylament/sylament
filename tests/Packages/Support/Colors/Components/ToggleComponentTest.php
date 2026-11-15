<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\Components\ToggleComponent;

it('resolves the track at 3:1 on gray 50 and, in dark mode, on gray 700, and the knob at 3:1 on white, for every catalog palette', function (): void {
    $policy = $this::getContainer()->get(ToggleComponent::class);
    $failures = [];

    foreach (Color::all() as $name => $palette) {
        $map = $policy->getColorMap($palette);

        foreach (['bg' => Color::Zinc[50], 'dark:bg' => Color::Zinc[700], 'text' => 'oklch(1 0 0)'] as $slot => $surface) {
            if (Color::calculateContrastRatio($palette[$map[$slot]], $surface) < 3.0) {
                $failures[] = "{$name} {$slot}";
            }
        }
    }

    expect($failures)->toBe([]);
});
