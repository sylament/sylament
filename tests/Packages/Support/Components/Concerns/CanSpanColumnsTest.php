<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Text;

it('spans the columns it is given: a number one column until lg, a map per breakpoint, a closure from the default breakpoint', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->columnSpan(2)))
        ->toContainElement('[style*="--col-span-default: span 1 / span 1"][style*="--col-span-lg: span 2 / span 2"]', 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')->columnSpan(['md' => 3])))
        ->toContainElement('[style*="--col-span-md: span 3 / span 3"]', 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')->columnSpan(fn (): int => 2)))
        ->toContainElement('[style*="--col-span-default: span 2 / span 2"]:not([style*="--col-span-lg"])', 'Total');
});

it('spans each breakpoint the closures it is given answer with a map', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->columnSpan(fn (): array => ['md' => 2])->columnSpan(fn (): array => ['xl' => 4])))
        ->toContainElement('[style*="--col-span-md: span 2 / span 2"][style*="--col-span-xl: span 4 / span 4"]', 'Total');
});

it('spans the full row when told to', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->columnSpanFull()))
        ->toContainElement('[style*="--col-span-default: 1 / -1"]', 'Total');
});

it('starts on the column it is given, a number at lg and a map per breakpoint', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->columnStart(2)))
        ->toContainElement('[style*="--col-start-lg: 2"]', 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')->columnStart(['md' => 1])))
        ->toContainElement('[style*="--col-start-md: 1"]', 'Total');
});
