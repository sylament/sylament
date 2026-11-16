<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Grid;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('lays its children out in the number of columns it is made with, two unless told', function (): void {
    expect($this->renderSchemaComponent(Grid::make(3)->components([Text::make('Street')])))
        ->toContainElement('.sy-sc-grid ' . Selectors::SCHEMA . SupportSelectors::columns('lg', 3), 'Street')
        ->and($this->renderSchemaComponent(Grid::make()->components([Text::make('Street')])))
        ->toContainElement('.sy-sc-grid ' . Selectors::SCHEMA . SupportSelectors::columns('lg', 2), 'Street');
});

it('renders a root of its own around its child schema', function (): void {
    expect($this->renderSchemaComponent(Grid::make()->components([Text::make('Street'), Text::make('City')])))
        ->toContainElement('.sy-sc-grid' . SupportSelectors::STANDALONE_ROOT . ' ' . Selectors::COMPONENT_CELL . ' ' . Selectors::TEXT, 'Street')
        ->toContainElement('.sy-sc-grid' . SupportSelectors::STANDALONE_ROOT . ' ' . Selectors::COMPONENT_CELL . ' ' . Selectors::TEXT, 'City');
});

it('carries its id and its extra attributes on its root, its own id winning over an extra one', function (): void {
    expect($this->renderSchemaComponent(Grid::make()->id('billing')->extraAttributes(['id' => 'other', 'class' => 'custom', 'data-region' => 'eu'])->components([Text::make('Street')])))
        ->toContainElement('.sy-sc-grid.custom#billing[data-region="eu"]', 'Street')
        ->not->toContainElement('#other');
});
