<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('collapses from a button in its header that controls its content, keyed by its id, and offers none unless collapsible', function (): void {
    expect($this->renderSchemaComponent(Section::make('Shipping')->collapsible()->id('ship')->components([Text::make('Body')])))
        ->toContainElement('section' . SupportSelectors::COLLAPSIBLE . '[data-controller="sylament--support--section"][data-sylament--support--section-id-value="ship"] h2', 'Shipping')
        ->toContainButtons(SupportSelectors::SECTION_COLLAPSE_BUTTON . '[aria-expanded="true"][aria-controls="ship-content"]', ['Collapse section'])
        ->and($this->renderSchemaComponent(Section::make('Shipping')->components([Text::make('Body')])))
        ->toContainElement('section:not(' . SupportSelectors::COLLAPSIBLE . '):not([data-controller]) h2', 'Shipping')
        ->not->toContainElement(SupportSelectors::SECTION_COLLAPSE_BUTTON);
});

it('starts collapsed when told, which makes it collapsible unless its collapsibility was set or waived', function (): void {
    expect($this->renderSchemaComponent(Section::make('Shipping')->collapsed()->components([Text::make('Body')])))
        ->toContainElement('section' . SupportSelectors::COLLAPSIBLE . SupportSelectors::COLLAPSED . '[data-sylament--support--section-collapsed-value="true"] h2', 'Shipping')
        ->toContainButtons(SupportSelectors::SECTION_COLLAPSE_BUTTON . '[aria-expanded="false"]', ['Expand section'])
        ->and($this->renderSchemaComponent(Section::make('Shipping')->collapsible(false)->collapsed()->components([Text::make('Body')])))
        ->toContainElement('section:not(' . SupportSelectors::COLLAPSIBLE . '):not(' . SupportSelectors::COLLAPSED . ')', 'Shipping Body')
        ->not->toContainElement(SupportSelectors::SECTION_COLLAPSE_BUTTON)
        ->and($this->renderSchemaComponent(Section::make('Shipping')->collapsed(shouldMakeComponentCollapsible: false)->components([Text::make('Body')])))
        ->toContainElement('section:not(' . SupportSelectors::COLLAPSIBLE . '):not(' . SupportSelectors::COLLAPSED . ')', 'Shipping Body')
        ->not->toContainElement(SupportSelectors::SECTION_COLLAPSE_BUTTON);
});

it('keeps its collapsed state across visits when told, and not otherwise', function (): void {
    expect($this->renderSchemaComponent(Section::make('Shipping')->collapsible()->persistCollapsed()->components([Text::make('Body')])))
        ->toContainElement('section' . SupportSelectors::COLLAPSIBLE . '[data-sylament--support--section-persist-value="true"] h2', 'Shipping')
        ->and($this->renderSchemaComponent(Section::make('Shipping')->collapsible()->components([Text::make('Body')])))
        ->toContainElement('section' . SupportSelectors::COLLAPSIBLE . ':not([data-sylament--support--section-persist-value]) h2', 'Shipping');
});
