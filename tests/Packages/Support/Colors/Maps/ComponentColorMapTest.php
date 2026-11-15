<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\Maps\ComponentColorMap;

it('resolves a slot to the first shade meeting its ratio against its surface, within its bounds', function (): void {
    expect(ComponentColorMap::make(Color::Zinc)
        ->slot('text', surface: '#ffffff')
        ->slot('dark:text', surface: '#000000', maxShade: 300, shouldStartFromDarkest: true)
        ->get())->toBe(['text' => 500, 'dark:text' => 300]);
});

it('resolves a slot no shade can satisfy to its declared fallback', function (): void {
    expect(ComponentColorMap::make(Color::Zinc)->slot('text', surface: '#ffffff', minRatio: 30.0, fallback: 700)->get())->toBe(['text' => 700]);
});
