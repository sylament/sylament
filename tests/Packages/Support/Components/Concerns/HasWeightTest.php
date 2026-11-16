<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Text;
use Sylament\Support\Enums\FontWeight;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('sets its font weight when given one, and none otherwise', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->weight(FontWeight::Bold)))
        ->toContainElement(SchemasSelectors::TEXT . SupportSelectors::font('bold'), 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')))
        ->toContainElement(SchemasSelectors::TEXT . SupportSelectors::NO_FONT, 'Total');
});

it('reads a known weight name as that weight, and any other name as a class of its own', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->weight('semibold')))
        ->toContainElement(SchemasSelectors::TEXT . SupportSelectors::font('semibold'), 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')->weight('heavy')))
        ->toContainElement(SchemasSelectors::TEXT . '.heavy' . SupportSelectors::NO_FONT, 'Total');
});
