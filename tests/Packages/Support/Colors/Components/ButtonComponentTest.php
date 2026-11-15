<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\Components\ButtonComponent;

it('resolves each filled-button state to a background and text at 4.5:1, short only where the palette allows no such pair', function (): void {
    $policy = $this::getContainer()->get(ButtonComponent::class);
    $states = ['rest' => ['bg', 'text'], 'hover' => ['hover:bg', 'hover:text'], 'dark' => ['dark:bg', 'dark:text'], 'dark hover' => ['dark:hover:bg', 'dark:hover:text']];
    $failures = [];

    foreach (Color::all() as $name => $palette) {
        $map = $policy->getColorMap($palette);

        foreach ($states as $state => [$background, $text]) {
            $textColor = $map[$text] === 0 ? 'oklch(1 0 0)' : $palette[$map[$text]];

            if (Color::calculateContrastRatio($palette[$map[$background]], $textColor) < 4.5) {
                $failures[] = "{$name} {$state}";
            }
        }
    }

    expect($failures)->toBe([
        'red hover',
        'red dark hover',
        'emerald dark',
        'teal dark',
        'cyan dark',
        'sky dark',
        'blue hover',
        'blue dark hover',
        'violet hover',
        'violet dark hover',
        'purple hover',
        'purple dark hover',
        'fuchsia hover',
        'fuchsia dark hover',
        'pink hover',
        'pink dark hover',
        'rose hover',
        'rose dark hover',
    ]);
});
