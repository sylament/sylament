<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Flex;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('renders the children it is made with side by side in a row of its own', function (): void {
    expect($this->renderSchemaComponent(Flex::make([Text::make('Name'), Text::make('Code')])))
        ->toContainElement(Selectors::FLEX . SupportSelectors::STANDALONE_ROOT, 'Name Code');
});

it('grows each child to fill the row unless told not to, never an action, and leaves a hidden child out', function (): void {
    $render = $this->renderSchemaComponent(Flex::make([
        Text::make('Name'),
        Text::make('Code')->grow(false),
        Text::make('Secret')->hidden(),
        Action::make('save'),
    ]));

    expect($render)
        ->toContainElement(Selectors::FLEX, 'Name Code Save')
        ->toContainElement(Selectors::FLEX . ' ' . Selectors::GROWABLE, 'Name')
        ->toContainElement(Selectors::FLEX . ' ' . SupportSelectors::BUTTON, 'Save')
        ->and($render->filter(Selectors::FLEX . ' ' . Selectors::GROWABLE))->toHaveCount(1);
});

it('packs its row densely when told, and not otherwise', function (): void {
    expect($this->renderSchemaComponent(Flex::make([Text::make('Name')])->dense()))
        ->toContainElement(Selectors::FLEX . '.sy-dense', 'Name')
        ->and($this->renderSchemaComponent(Flex::make([Text::make('Name')])))
        ->toContainElement(Selectors::FLEX . ':not(.sy-dense)', 'Name');
});

it('carries an id on its row only as an extra attribute', function (): void {
    expect($this->renderSchemaComponent(Flex::make([Text::make('Name')])->id('row')))
        ->toContainElement(Selectors::FLEX . ':not([id])', 'Name')
        ->and($this->renderSchemaComponent(Flex::make([Text::make('Name')])->extraAttributes(['id' => 'row'])))
        ->toContainElement(Selectors::FLEX . '#row', 'Name');
});
