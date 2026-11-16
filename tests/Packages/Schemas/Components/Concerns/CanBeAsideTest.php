<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('sets its heading aside from its box when told, and not when unset, turned off or reset', function (): void {
    expect($this->renderSchemaComponent(Section::make('Shipping')->aside()->components([Text::make('Body')])))
        ->toContainElement('section' . SupportSelectors::ASIDE . ' h2', 'Shipping')
        ->and($this->renderSchemaComponent(Section::make('Shipping')->components([Text::make('Body')])))
        ->toContainElement('section:not(' . SupportSelectors::ASIDE . ') h2', 'Shipping')
        ->and($this->renderSchemaComponent(Section::make('Shipping')->aside(false)->components([Text::make('Body')])))
        ->toContainElement('section:not(' . SupportSelectors::ASIDE . ') h2', 'Shipping')
        ->and($this->renderSchemaComponent(Section::make('Shipping')->aside()->aside(null)->components([Text::make('Body')])))
        ->toContainElement('section:not(' . SupportSelectors::ASIDE . ') h2', 'Shipping');
});
