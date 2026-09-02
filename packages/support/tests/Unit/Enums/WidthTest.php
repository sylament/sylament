<?php

declare(strict_types=1);

use Sylament\Support\Enums\Width;

it('carries every Filament width token with its backing value', function (): void {
    $expected = [
        'ThreeExtraSmall' => '3xs', 'TwoExtraSmall' => '2xs', 'ExtraSmall' => 'xs', 'Small' => 'sm',
        'Medium' => 'md', 'Large' => 'lg', 'ExtraLarge' => 'xl', 'TwoExtraLarge' => '2xl',
        'ThreeExtraLarge' => '3xl', 'FourExtraLarge' => '4xl', 'FiveExtraLarge' => '5xl',
        'SixExtraLarge' => '6xl', 'SevenExtraLarge' => '7xl', 'None' => 'none', 'Full' => 'full',
        'MinContent' => 'min', 'MaxContent' => 'max', 'FitContent' => 'fit', 'Prose' => 'prose',
        'Container' => 'container', 'ScreenSmall' => 'screen-sm', 'ScreenMedium' => 'screen-md',
        'ScreenLarge' => 'screen-lg', 'ScreenExtraLarge' => 'screen-xl',
        'ScreenTwoExtraLarge' => 'screen-2xl', 'Screen' => 'screen',
    ];

    $actual = [];

    foreach (Width::cases() as $case) {
        $actual[$case->name] = $case->value;
    }

    expect($actual)->toBe($expected)
        ->and(Width::tryFrom('7xl'))->toBe(Width::SevenExtraLarge)
        ->and(Width::tryFrom('max-w-[42rem]'))->toBeNull();
});
