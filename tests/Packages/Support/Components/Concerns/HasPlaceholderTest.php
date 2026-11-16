<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;

it('shows its placeholder in place of a blank state, and none for a filled state or without one', function (): void {
    $filled = $this->renderViewComponent(TextColumn::make('note')->record(['note' => 'Fragile'])->placeholder('No note'));
    $unset = $this->renderViewComponent(TextColumn::make('note')->record(['note' => null]));

    expect($this->renderViewComponent(TextColumn::make('note')->record(['note' => null])->placeholder('No note')))
        ->toContainElement('.sy-ta-placeholder', 'No note')
        ->and($filled)->toContainElement('.sy', 'Fragile')
        ->and($filled->filter('.sy-ta-placeholder')->count())->toBe(0)
        ->and($unset)->toContainElement('.sy')
        ->and($unset->filter('.sy-ta-placeholder')->count())->toBe(0);
});
