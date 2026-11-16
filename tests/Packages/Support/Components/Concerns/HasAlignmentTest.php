<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Flex;
use Sylament\Schemas\Components\Text;
use Sylament\Support\Enums\Alignment;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('aligns its content by the alignment it is given, and carries no alignment without one', function (): void {
    expect($this->renderSchemaComponent(Flex::make([Text::make('Total')])->alignment(Alignment::Center)))
        ->toContainElement(SchemasSelectors::FLEX . SupportSelectors::align('center'), 'Total')
        ->and($this->renderSchemaComponent(Flex::make([Text::make('Total')])))
        ->toContainElement(SchemasSelectors::FLEX . SupportSelectors::NO_ALIGN, 'Total');
});

it('reads a known alignment name as that alignment, and any other name as a class of its own', function (): void {
    expect($this->renderSchemaComponent(Flex::make([Text::make('Total')])->alignment('end')))
        ->toContainElement(SchemasSelectors::FLEX . SupportSelectors::align('end'), 'Total')
        ->and($this->renderSchemaComponent(Flex::make([Text::make('Total')])->alignment('my-align')))
        ->toContainElement(SchemasSelectors::FLEX . '.my-align' . SupportSelectors::NO_ALIGN, 'Total');
});

it('aligns by its helper while the condition holds, and not otherwise', function (string $helper, string $class): void {
    expect($this->renderSchemaComponent(Flex::make([Text::make('Total')])->{$helper}()))
        ->toContainElement(SchemasSelectors::FLEX . $class, 'Total')
        ->and($this->renderSchemaComponent(Flex::make([Text::make('Total')])->{$helper}(false)))
        ->toContainElement(SchemasSelectors::FLEX . SupportSelectors::NO_ALIGN, 'Total');
})->with([
    'alignStart()' => ['alignStart', SupportSelectors::align('start')],
    'alignCenter()' => ['alignCenter', SupportSelectors::align('center')],
    'alignEnd()' => ['alignEnd', SupportSelectors::align('end')],
    'alignJustify()' => ['alignJustify', SupportSelectors::align('justify')],
    'alignBetween()' => ['alignBetween', SupportSelectors::align('between')],
    'alignLeft()' => ['alignLeft', SupportSelectors::align('left')],
    'alignRight()' => ['alignRight', SupportSelectors::align('right')],
]);
