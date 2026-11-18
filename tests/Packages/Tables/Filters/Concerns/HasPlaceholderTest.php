<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\FilterableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\ShapedJarFilterTable;

it('offers All as the empty choice of a single or a multiple select, or the placeholder it is given', function (): void {
    expect($this->createLiveComponent(FilterableLarderJarTable::class)->render()->crawler())
        ->toContainElement(Selectors::filterField('seal', 'value') . ' option[value=""]', 'All')
        ->toContainElement(Selectors::filterField('seals', 'values') . ' option[value=""]', 'All')
        ->and($this->createLiveComponent(ShapedJarFilterTable::class)->render()->crawler())
        ->toContainElement(Selectors::filterField('kind', 'value') . ' option[value=""]', 'Any seal');
});
