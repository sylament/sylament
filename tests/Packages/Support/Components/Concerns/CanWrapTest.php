<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Support\Selectors;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;

it('wraps its text when told to, and keeps it on one line otherwise', function (): void {
    expect($this->renderViewComponent(TextColumn::make('description')->record(['description' => 'A brass desk lamp'])->wrap()))
        ->toContainElement(TablesSelectors::TEXT_COLUMN . Selectors::WRAPPED, 'A brass desk lamp')
        ->and($this->renderViewComponent(TextColumn::make('description')->record(['description' => 'A brass desk lamp'])))
        ->toContainElement(TablesSelectors::TEXT_COLUMN . ':not(' . Selectors::WRAPPED . ')', 'A brass desk lamp');
});
