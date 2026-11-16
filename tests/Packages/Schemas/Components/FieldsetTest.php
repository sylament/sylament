<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Fieldset;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('is captioned by the label it is made with, and by no legend when made without one', function (): void {
    expect($this->renderSchemaComponent(Fieldset::make('Address')->components([Text::make('Street')])))
        ->toContainElement(Selectors::FIELDSET . ' legend', 'Address')
        ->and($this->renderSchemaComponent(Fieldset::make()->components([Text::make('Street')])))
        ->toContainElement(Selectors::FIELDSET, 'Street')
        ->not->toContainElement('legend');
});

it('lays its children out in two columns unless given its own', function (): void {
    expect($this->renderSchemaComponent(Fieldset::make('Address')->components([Text::make('Street'), Text::make('City')])))
        ->toContainElement(Selectors::FIELDSET . ' ' . Selectors::SCHEMA . SupportSelectors::columns('lg', 2), 'Street City')
        ->and($this->renderSchemaComponent(Fieldset::make('Address')->columns(3)->components([Text::make('Street'), Text::make('City')])))
        ->toContainElement(Selectors::FIELDSET . ' ' . Selectors::SCHEMA . SupportSelectors::columns('lg', 3), 'Street City')
        ->not->toContainElement(Selectors::FIELDSET . ' ' . Selectors::SCHEMA . SupportSelectors::columns('lg', 2));
});

it('marks no asterisk on its legend for a required field it holds', function (): void {
    expect($this->renderSchemaComponent(Fieldset::make('Address')->components([TextInput::make('street')->required()])))
        ->toContainElement(Selectors::FIELDSET . ' input[name="street"][required]')
        ->toContainElement(Selectors::FIELDSET . ' legend', 'Address')
        ->not->toContainElement(Selectors::FIELDSET . ' legend sup');
});

it('renders a fieldset captioned by its legend around its child schema', function (): void {
    expect($this->renderSchemaComponent(Fieldset::make('Address')->components([Text::make('Street')])))
        ->toContainElement('fieldset' . SupportSelectors::STANDALONE_ROOT . SupportSelectors::FIELDSET . Selectors::FIELDSET . ' legend', 'Address')
        ->toContainElement(Selectors::FIELDSET . ' ' . Selectors::COMPONENT_CELL . ' ' . Selectors::TEXT, 'Street');
});

it('carries its id and its extra attributes on its root, its own id winning over an extra one', function (): void {
    $render = $this->renderSchemaComponent(Fieldset::make('Address')->id('billing')->extraAttributes(['id' => 'other', 'class' => 'custom', 'data-region' => 'eu'])->components([Text::make('Street')]));

    expect($render)
        ->toContainElement('fieldset#billing.custom[data-region="eu"] legend', 'Address')
        ->not->toContainElement('#other');
});
