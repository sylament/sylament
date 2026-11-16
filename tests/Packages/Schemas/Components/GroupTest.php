<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('stacks the children it is made with in one column', function (): void {
    expect($this->renderSchemaComponent(Group::make([Text::make('Street'), Text::make('City')])))
        ->toContainElement('.sy-sc-group ' . Selectors::SCHEMA . SupportSelectors::ONE_COLUMN, 'Street City');
});

it('is made with a single component or a string as well as an array', function (Closure $children): void {
    expect($this->renderSchemaComponent(Group::make($children())))
        ->toContainElement('.sy-sc-group ' . Selectors::COMPONENT_CELL . ' ' . Selectors::TEXT, 'Street');
})->with([
    'a component' => [static fn (): Text => Text::make('Street')],
    'a string' => [static fn (): string => 'Street'],
]);

it('renders a root of its own around its child schema', function (): void {
    expect($this->renderSchemaComponent(Group::make([Text::make('Street'), Text::make('City')])))
        ->toContainElement('.sy-sc-group' . SupportSelectors::STANDALONE_ROOT . ' ' . Selectors::COMPONENT_CELL . ' ' . Selectors::TEXT, 'Street')
        ->toContainElement('.sy-sc-group' . SupportSelectors::STANDALONE_ROOT . ' ' . Selectors::COMPONENT_CELL . ' ' . Selectors::TEXT, 'City');
});

it('carries its id and its extra attributes on its root, its own id winning over an extra one', function (): void {
    expect($this->renderSchemaComponent(Group::make([Text::make('Street')])->id('billing')->extraAttributes(['id' => 'other', 'class' => 'custom', 'data-region' => 'eu'])))
        ->toContainElement('.sy-sc-group.custom#billing[data-region="eu"]', 'Street')
        ->not->toContainElement('#other');
});
