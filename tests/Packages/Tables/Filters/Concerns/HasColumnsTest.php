<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\ShapedJarFilterTable;

it('lays its fields out in the columns it is given', function (): void {
    expect($this->createLiveComponent(ShapedJarFilterTable::class)->render()->crawler())
        ->toContainElement(Selectors::filterGroup('stocked') . ' ' . SupportSelectors::columns('lg', 2));
});
