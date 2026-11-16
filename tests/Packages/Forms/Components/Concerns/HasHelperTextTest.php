<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;

it('shows its text below the content, and nothing when blank', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('name')->helperText('As on your passport.')))
        ->toContainElement('.sy-fo-field-content-col ' . SchemasSelectors::TEXT, 'As on your passport.')
        ->and($this->renderSchemaComponent(TextInput::make('name')->helperText('  ')))
        ->toContainElement('input[name="name"]')
        ->not->toContainElement(SchemasSelectors::TEXT);
});
