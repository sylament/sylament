<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Flex;
use Sylament\Schemas\Components\Text;
use Sylament\Support\Enums\VerticalAlignment;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Tables\AlignedColumnsTable;

it('aligns its content vertically by the alignment it is given, a known name as that alignment and any other as a class of its own', function (): void {
    expect($this->renderSchemaComponent(Flex::make([Text::make('Total')])->verticalAlignment(VerticalAlignment::End)))
        ->toContainElement(SchemasSelectors::FLEX . '.sy-vertical-align-end', 'Total')
        ->and($this->renderSchemaComponent(Flex::make([Text::make('Total')])->verticalAlignment('center')))
        ->toContainElement(SchemasSelectors::FLEX . '.sy-vertical-align-center', 'Total')
        ->and($this->renderSchemaComponent(Flex::make([Text::make('Total')])->verticalAlignment('my-align')))
        ->toContainElement(SchemasSelectors::FLEX . '.my-align:not([class*="sy-vertical-align-"])', 'Total');
});

it('aligns vertically by its helper while the condition holds, and not otherwise', function (string $helper, string $class): void {
    expect($this->renderSchemaComponent(Flex::make([Text::make('Total')])->{$helper}()))
        ->toContainElement(SchemasSelectors::FLEX . $class, 'Total')
        ->and($this->renderSchemaComponent(Flex::make([Text::make('Total')])->{$helper}(false)))
        ->toContainElement(SchemasSelectors::FLEX . ':not([class*="sy-vertical-align-"])', 'Total');
})->with([
    'verticallyAlignStart()' => ['verticallyAlignStart', '.sy-vertical-align-start'],
    'verticallyAlignCenter()' => ['verticallyAlignCenter', '.sy-vertical-align-center'],
    'verticallyAlignEnd()' => ['verticallyAlignEnd', '.sy-vertical-align-end'],
]);

it("gives a table cell its column's vertical alignment class, a custom class as it is, and none unaligned", function (): void {
    expect($this->createLiveComponent(AlignedColumnsTable::class)->render()->crawler())
        ->toContainElement('td.sy-ta-cell-sku.sy-vertical-align-start', 'L-1')
        ->toContainElement('td.sy-ta-cell-note.my-align:not([class*="sy-vertical-align-"])', 'Fragile')
        ->toContainElement('td.sy-ta-cell-name:not([class*="sy-vertical-align-"])', 'Lamp');
});
