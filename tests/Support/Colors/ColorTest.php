<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;

it('measures contrast between two colours', function (string $a, string $b, float $ratio): void {
    expect(Color::calculateContrastRatio($a, $b))->toEqualWithDelta($ratio, 0.01);
})->with([
    'white on black' => ['#ffffff', '#000000', 21.0],
    'a colour on itself' => ['#3b82f6', '#3b82f6', 1.0],
]);
