<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Flex;
use Sylament\Schemas\Components\Text;
use Sylament\Support\Enums\VerticalAlignment;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\AlignedColumnsTable;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;

it('aligns its content vertically by the alignment it is given, a known name as that alignment and any other as a class of its own', function (): void {
    expect($this->renderSchemaComponent(Flex::make([Text::make('Total')])->verticalAlignment(VerticalAlignment::End)))
        ->toContainElement(SchemasSelectors::FLEX . SupportSelectors::verticalAlign('end'), 'Total')
        ->and($this->renderSchemaComponent(Flex::make([Text::make('Total')])->verticalAlignment('center')))
        ->toContainElement(SchemasSelectors::FLEX . SupportSelectors::verticalAlign('center'), 'Total')
        ->and($this->renderSchemaComponent(Flex::make([Text::make('Total')])->verticalAlignment('my-align')))
        ->toContainElement(SchemasSelectors::FLEX . '.my-align' . SupportSelectors::NO_VERTICAL_ALIGN, 'Total');
});

it('aligns vertically by its helper while the condition holds, and not otherwise', function (string $helper, string $class): void {
    expect($this->renderSchemaComponent(Flex::make([Text::make('Total')])->{$helper}()))
        ->toContainElement(SchemasSelectors::FLEX . $class, 'Total')
        ->and($this->renderSchemaComponent(Flex::make([Text::make('Total')])->{$helper}(false)))
        ->toContainElement(SchemasSelectors::FLEX . SupportSelectors::NO_VERTICAL_ALIGN, 'Total');
})->with([
    'verticallyAlignStart()' => ['verticallyAlignStart', SupportSelectors::verticalAlign('start')],
    'verticallyAlignCenter()' => ['verticallyAlignCenter', SupportSelectors::verticalAlign('center')],
    'verticallyAlignEnd()' => ['verticallyAlignEnd', SupportSelectors::verticalAlign('end')],
]);

it("gives a table cell its column's vertical alignment class, a custom class as it is, and none unaligned", function (): void {
    expect($this->createLiveComponent(AlignedColumnsTable::class)->render()->crawler())
        ->toContainElement('td' . TablesSelectors::cellClass('sku') . SupportSelectors::verticalAlign('start'), 'L-1')
        ->toContainElement('td' . TablesSelectors::cellClass('note') . '.my-align' . SupportSelectors::NO_VERTICAL_ALIGN, 'Fragile')
        ->toContainElement('td' . TablesSelectors::cellClass('name') . SupportSelectors::NO_VERTICAL_ALIGN, 'Lamp');
});
