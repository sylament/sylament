<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('lays its content out inline when told to', function (): void {
    expect($this->renderViewComponent(TextColumn::make('name')->record(['name' => 'Lamp'])->inline()))
        ->toContainElement(Selectors::TEXT_COLUMN . SchemasSelectors::INLINE, 'Lamp')
        ->and($this->renderViewComponent(TextColumn::make('name')->record(['name' => 'Lamp'])))
        ->toContainElement(Selectors::TEXT_COLUMN . ':not(' . SchemasSelectors::INLINE . ')', 'Lamp');
});
