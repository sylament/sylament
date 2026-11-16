<?php

declare(strict_types=1);

use Sylament\Support\Colors\ColorManager;
use Sylament\Tables\Colors\Components\TextColumnComponent\ItemComponent;

it('emits gray as a colour of its own, so a gray cell differs from an uncoloured one', function (): void {
    expect($this::getContainer()->get(ColorManager::class)->getComponentClasses(ItemComponent::class, 'gray'))->toContain('sy-color', 'sy-color-gray');
});
