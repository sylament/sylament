<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Fieldset;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Schemas\Selectors;

it('renders the id it is given, and its key without one', function (): void {
    expect($this->renderSchemaComponent(Fieldset::make('Address')->id('billing')->components([Text::make('a')])))
        ->toContainElement('fieldset[id="billing"] legend', 'Address')
        ->and($this->renderSchemaComponent(Section::make('Billing details')->components([Text::make('a')])))
        ->toContainElement(Selectors::SECTION . '[id="schema.billing-details::section"]');
});

it('strips from its id the characters that would break out of an attribute or a script string', function (): void {
    expect($this->renderSchemaComponent(Fieldset::make('Address')->id("bill\"ing'<x>`")->components([Text::make('a')])))
        ->toContainElement('fieldset[id="billingx"] legend', 'Address');
});
