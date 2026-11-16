<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('lays its content out in one column until told otherwise', function (): void {
    expect($this->renderSchemaComponent(Section::make('Plain')->components([Text::make('a')])))
        ->toContainElement(Selectors::SECTION_CONTENT . SupportSelectors::ONE_COLUMN);
});

it('lays its content out in the columns of every call: an int counts from lg, each call merges over the earlier ones and a closure adds its own', function (): void {
    $section = Section::make('Folded')
        ->columns(['md' => 2])
        ->columns(3)
        ->columns(static fn (): array => ['xl' => 6])
        ->components([Text::make('a')]);

    expect($this->renderSchemaComponent($section))
        ->toContainElement(Selectors::SECTION_CONTENT . SupportSelectors::columns('md', 2) . SupportSelectors::columns('lg', 3) . SupportSelectors::columns('xl', 6))
        ->not->toContainElement(Selectors::SECTION_CONTENT . SupportSelectors::columns('default', 3));
});

it('lays its content out from lg in the count a closure answers with', function (): void {
    expect($this->renderSchemaComponent(Section::make('Counted')->columns(static fn (): int => 4)->components([Text::make('a')])))
        ->toContainElement(Selectors::SECTION_CONTENT . SupportSelectors::columns('lg', 4));
});
