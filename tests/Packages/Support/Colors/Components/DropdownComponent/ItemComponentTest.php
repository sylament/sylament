<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\Components\DropdownComponent\ItemComponent;

it('resolves item text readable on white and its own lightest shade from 600 up, and in dark mode from 400 down, for every catalog palette', function (): void {
    $policy = $this::getContainer()->get(ItemComponent::class);
    $failures = [];

    foreach (Color::all() as $name => $palette) {
        $map = $policy->getColorMap($palette);
        $surfaces = [
            'text' => 'oklch(1 0 0)',
            'hover:text' => $palette[50],
            'dark:text' => Color::Zinc[900],
            'dark:hover:text' => $palette[950],
        ];

        foreach ($surfaces as $slot => $surface) {
            $inBounds = str_starts_with($slot, 'dark:') ? $map[$slot] <= 400 : $map[$slot] >= 600;

            if (!$inBounds || Color::calculateContrastRatio($palette[$map[$slot]], $surface) < 4.5) {
                $failures[] = "{$name} {$slot}";
            }
        }
    }

    expect($failures)->toBe([]);
});
