<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Fieldset;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('names its fieldset by its label', function (): void {
    expect($this->renderSchemaComponent(Fieldset::make()->label('Shipping')->components([Text::make('a')])))
        ->toContainElement(Selectors::FIELDSET . ' legend', 'Shipping');
});

it('keeps a hidden label in the markup, hidden only from view', function (): void {
    expect($this->renderSchemaComponent(Fieldset::make('Shipping')->hiddenLabel()->components([Text::make('a')])))
        ->toContainElement(Selectors::FIELDSET . SupportSelectors::FIELDSET_LABEL_HIDDEN . ' legend', 'Shipping');
});
