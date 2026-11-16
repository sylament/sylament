<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('spans the columns it is given: a number one column until lg, a map per breakpoint, a closure from the default breakpoint', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->columnSpan(2)))
        ->toContainElement(SupportSelectors::columnSpan('default', 1) . SupportSelectors::columnSpan('lg', 2), 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')->columnSpan(['md' => 3])))
        ->toContainElement(SupportSelectors::columnSpan('md', 3), 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')->columnSpan(fn (): int => 2)))
        ->toContainElement(SupportSelectors::columnSpan('default', 2) . ':not(' . SupportSelectors::columnSpan('lg') . ')', 'Total');
});

it('spans each breakpoint the closures it is given answer with a map', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->columnSpan(fn (): array => ['md' => 2])->columnSpan(fn (): array => ['xl' => 4])))
        ->toContainElement(SupportSelectors::columnSpan('md', 2) . SupportSelectors::columnSpan('xl', 4), 'Total');
});

it('spans the full row when told to', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->columnSpanFull()))
        ->toContainElement(SupportSelectors::columnSpan('default', 'full'), 'Total');
});

it('starts on the column it is given, a number at lg and a map per breakpoint', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->columnStart(2)))
        ->toContainElement(SupportSelectors::columnStart('lg', 2), 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')->columnStart(['md' => 1])))
        ->toContainElement(SupportSelectors::columnStart('md', 1), 'Total');
});
