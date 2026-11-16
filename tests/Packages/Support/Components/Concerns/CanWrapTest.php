<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;

it('wraps its text when told to, and keeps it on one line otherwise', function (): void {
    expect($this->renderViewComponent(TextColumn::make('description')->record(['description' => 'A brass desk lamp'])->wrap()))
        ->toContainElement('.sy.sy-wrapped', 'A brass desk lamp')
        ->and($this->renderViewComponent(TextColumn::make('description')->record(['description' => 'A brass desk lamp'])))
        ->toContainElement('.sy:not(.sy-wrapped)', 'A brass desk lamp');
});
