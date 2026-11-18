<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;

it('puts the clamp on the item when one is set and none otherwise', function (): void {
    expect($this->renderViewComponent(TextColumn::make('note')->record(['note' => 'A brass desk lamp'])->lineClamp(3)))
        ->toContainElement(TablesSelectors::TEXT_ITEM . '[style*="--sy-line-clamp: 3"]', 'A brass desk lamp')
        ->and($this->renderViewComponent(TextColumn::make('note')->record(['note' => 'A brass desk lamp'])))
        ->toContainElement(TablesSelectors::TEXT_ITEM . ':not([style*="--sy-line-clamp"])', 'A brass desk lamp');
});
