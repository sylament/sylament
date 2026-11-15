<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\Components\LinkComponent;

it('resolves link text readable on gray 50 and, in dark mode, on gray 700, for every catalog palette', function (): void {
    $policy = $this::getContainer()->get(LinkComponent::class);
    $failures = [];

    foreach (Color::all() as $name => $palette) {
        $map = $policy->getColorMap($palette);

        foreach (['text' => Color::Zinc[50], 'dark:text' => Color::Zinc[700]] as $slot => $surface) {
            if (Color::calculateContrastRatio($palette[$map[$slot]], $surface) < 4.5) {
                $failures[] = "{$name} {$slot}";
            }
        }
    }

    expect($failures)->toBe([]);
});
