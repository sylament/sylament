<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('lays its content out inline when told to', function (): void {
    expect($this->renderViewComponent(TextColumn::make('name')->record(['name' => 'Lamp'])->inline()))
        ->toContainElement(Selectors::TEXT_COLUMN . SupportSelectors::INLINE, 'Lamp')
        ->and($this->renderViewComponent(TextColumn::make('name')->record(['name' => 'Lamp'])))
        ->toContainElement(Selectors::TEXT_COLUMN . ':not(' . SupportSelectors::INLINE . ')', 'Lamp');
});
