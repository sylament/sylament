<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('divides its header, content and footer when told, and not otherwise', function (): void {
    expect($this->renderSchemaComponent(Section::make('Shipping')->divided()->components([Text::make('Body')])))
        ->toContainElement('section' . SupportSelectors::DIVIDED . ' h2', 'Shipping')
        ->and($this->renderSchemaComponent(Section::make('Shipping')->divided(false)->components([Text::make('Body')])))
        ->toContainElement('section:not(' . SupportSelectors::DIVIDED . ') h2', 'Shipping')
        ->and($this->renderSchemaComponent(Section::make('Shipping')->components([Text::make('Body')])))
        ->toContainElement('section:not(' . SupportSelectors::DIVIDED . ') h2', 'Shipping');
});
