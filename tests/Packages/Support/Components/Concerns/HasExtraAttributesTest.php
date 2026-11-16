<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Fieldset;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;

it('renders its extra attributes on its root, a later call replacing them unless it merges', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->extraAttributes(['data-step' => '1'])->extraAttributes(['data-row' => '2'])))
        ->toContainElement(SchemasSelectors::TEXT . '[data-row="2"]:not([data-step])', 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')->extraAttributes(['data-step' => '1'])->extraAttributes(['data-row' => '2'], merge: true)))
        ->toContainElement(SchemasSelectors::TEXT . '[data-step="1"][data-row="2"]', 'Total');
});

it("lets an earlier entry win a colliding attribute, and puts a later entry's classes first", function (): void {
    $text = $this->renderSchemaComponent(Text::make('Total')
        ->extraAttributes(['class' => 'one', 'title' => 'First'])
        ->extraAttributes(['class' => 'two three', 'title' => 'Second'], merge: true));

    expect($text)->toContainElement(SchemasSelectors::TEXT . '.one.two.three[title="First"]', 'Total')
        ->and($text->filter(SchemasSelectors::TEXT)->attr('class'))->toEndWith('two three one');
});

it('renders its extra attributes on a fieldset beside its own class', function (): void {
    expect($this->renderSchemaComponent(Fieldset::make('Address')->extraAttributes(['class' => 'billing', 'data-region' => 'eu'])))
        ->toContainElement('fieldset.sy-sc-fieldset.billing[data-region="eu"] legend', 'Address');
});
