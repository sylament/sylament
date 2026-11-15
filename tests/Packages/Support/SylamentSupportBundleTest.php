<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;

it('registers the palettes under sylament.colors, a dashed name kept as written', function (): void {
    $this::bootKernel(['environment' => 'registered_colors']);

    $colors = $this::getContainer()->get(ColorManager::class);

    expect($colors->getColor('brand'))->toBe(Color::generatePalette('#7c3aed'))
        ->and($colors->getColor('brand-alt'))->toBe(Color::Rose)
        ->and($colors->getColor('brand_alt'))->toBeNull();
});
