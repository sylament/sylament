<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Schemas\Selectors;

it('lays its content out in one column until told otherwise', function (): void {
    expect($this->renderSchemaComponent(Section::make('Plain')->components([Text::make('a')])))
        ->toContainElement(Selectors::SECTION_CONTENT . '[style="--cols-default: repeat(1, minmax(0, 1fr))"]');
});

it('lays its content out in the columns of every call: an int counts from lg, an array merges over it and a closure adds its own', function (): void {
    $section = Section::make('Folded')
        ->columns(3)
        ->columns(['md' => 2, 'lg' => 4])
        ->columns(static fn (): array => ['xl' => 6])
        ->components([Text::make('a')]);

    expect($this->renderSchemaComponent($section))
        ->toContainElement(Selectors::SECTION_CONTENT . '[style*="--cols-md: repeat(2, minmax(0, 1fr))"][style*="--cols-lg: repeat(4, minmax(0, 1fr))"][style*="--cols-xl: repeat(6, minmax(0, 1fr))"]')
        ->not->toContainElement(Selectors::SECTION_CONTENT . '[style*="--cols-lg: repeat(3,"]');
});
