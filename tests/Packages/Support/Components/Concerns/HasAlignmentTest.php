<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Flex;
use Sylament\Schemas\Components\Text;
use Sylament\Support\Enums\Alignment;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;

it('aligns its content by the alignment it is given, and carries no alignment without one', function (): void {
    expect($this->renderSchemaComponent(Flex::make([Text::make('Total')])->alignment(Alignment::Center)))
        ->toContainElement(SchemasSelectors::FLEX . '.sy-align-center', 'Total')
        ->and($this->renderSchemaComponent(Flex::make([Text::make('Total')])))
        ->toContainElement(SchemasSelectors::FLEX . ':not([class*="sy-align-"])', 'Total');
});

it('reads a known alignment name as that alignment, and any other name as a class of its own', function (): void {
    expect($this->renderSchemaComponent(Flex::make([Text::make('Total')])->alignment('end')))
        ->toContainElement(SchemasSelectors::FLEX . '.sy-align-end', 'Total')
        ->and($this->renderSchemaComponent(Flex::make([Text::make('Total')])->alignment('my-align')))
        ->toContainElement(SchemasSelectors::FLEX . '.my-align:not([class*="sy-align-"])', 'Total');
});

it('aligns by its helper while the condition holds, and not otherwise', function (string $helper, string $class): void {
    expect($this->renderSchemaComponent(Flex::make([Text::make('Total')])->{$helper}()))
        ->toContainElement(SchemasSelectors::FLEX . $class, 'Total')
        ->and($this->renderSchemaComponent(Flex::make([Text::make('Total')])->{$helper}(false)))
        ->toContainElement(SchemasSelectors::FLEX . ':not([class*="sy-align-"])', 'Total');
})->with([
    'alignStart()' => ['alignStart', '.sy-align-start'],
    'alignCenter()' => ['alignCenter', '.sy-align-center'],
    'alignEnd()' => ['alignEnd', '.sy-align-end'],
    'alignJustify()' => ['alignJustify', '.sy-align-justify'],
    'alignBetween()' => ['alignBetween', '.sy-align-between'],
    'alignLeft()' => ['alignLeft', '.sy-align-left'],
    'alignRight()' => ['alignRight', '.sy-align-right'],
]);
