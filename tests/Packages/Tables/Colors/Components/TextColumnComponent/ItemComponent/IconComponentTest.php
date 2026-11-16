<?php

declare(strict_types=1);

use Sylament\Support\Colors\ColorManager;
use Sylament\Tables\Colors\Components\TextColumnComponent\ItemComponent\IconComponent;

it('emits only the palette swap for every shipped colour, leaving the shade to the stylesheet, and nothing at all for gray', function (): void {
    $colors = $this::getContainer()->get(ColorManager::class);

    foreach (array_keys(ColorManager::DEFAULT_COLORS) as $name) {
        expect($colors->getComponentClasses(IconComponent::class, $name))->toBe($name === 'gray' ? [] : ['sy-color', "sy-color-{$name}"]);
    }
});
