<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Text;
use Sylament\Support\Enums\FontFamily;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('sets its font family when given one, and none otherwise', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->fontFamily(FontFamily::Mono)))
        ->toContainElement(SchemasSelectors::TEXT . SupportSelectors::font('mono'), 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')))
        ->toContainElement(SchemasSelectors::TEXT . SupportSelectors::NO_FONT, 'Total');
});

it('reads a known family name as that family, and any other name as a class of its own', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->fontFamily('serif')))
        ->toContainElement(SchemasSelectors::TEXT . SupportSelectors::font('serif'), 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')->fontFamily('my-font')))
        ->toContainElement(SchemasSelectors::TEXT . '.my-font' . SupportSelectors::NO_FONT, 'Total');
});
