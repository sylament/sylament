<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\ShapedJarFilterTable;

it('places its field in the cell its span and start give, a span closure evaluated against its group', function (): void {
    expect($this->createLiveComponent(ShapedJarFilterTable::class)->render()->crawler())
        ->toContainElement(SupportSelectors::columnSpan('lg', 2) . ' ' . Selectors::filterGroup('sealed'))
        ->toContainElement(SupportSelectors::columnSpan('default', 'full') . ' ' . Selectors::filterGroup('stocked'))
        ->toContainElement(SupportSelectors::columnStart('lg', 2) . ' ' . Selectors::filterGroup('waxed'))
        ->toContainElement(SupportSelectors::columnSpan('default', 3) . ' ' . Selectors::filterGroup('screwed'));
});
