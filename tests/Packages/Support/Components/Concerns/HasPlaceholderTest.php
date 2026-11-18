<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;

it('shows its placeholder in place of a blank state, and none for a filled state or without one', function (): void {
    $filled = $this->renderViewComponent(TextColumn::make('note')->record(['note' => 'Fragile'])->placeholder('No note'));
    $unset = $this->renderViewComponent(TextColumn::make('note')->record(['note' => null]));

    expect($this->renderViewComponent(TextColumn::make('note')->record(['note' => null])->placeholder('No note')))
        ->toContainElement(TablesSelectors::PLACEHOLDER, 'No note')
        ->and($filled)->toContainElement(TablesSelectors::TEXT_COLUMN, 'Fragile')
        ->and($filled->filter(TablesSelectors::PLACEHOLDER)->count())->toBe(0)
        ->and($unset)->toContainElement(TablesSelectors::TEXT_COLUMN)
        ->and($unset->filter(TablesSelectors::PLACEHOLDER)->count())->toBe(0);
});
