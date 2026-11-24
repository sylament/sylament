<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;

use function Symfony\Component\Translation\t;

it('shows its text below the content, and nothing when blank', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('name')->helperText('As on your passport.')))
        ->toContainElement(Selectors::CONTENT_COLUMN . ' ' . SchemasSelectors::TEXT, 'As on your passport.')
        ->and($this->renderSchemaComponent(TextInput::make('name')->helperText('  ')))
        ->toContainElement('input[name="name"]:not([aria-describedby])')
        ->not->toContainElement(SchemasSelectors::TEXT);
});

it('describes the control by nothing once another below-content slot replaces it', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('name')->helperText('As on your passport.')->belowContent('As on your licence.')))
        ->toContainElement(Selectors::CONTENT_COLUMN . ' ' . SchemasSelectors::TEXT, 'As on your licence.')
        ->toContainElement('input[name="name"]:not([aria-describedby])')
        ->not->toContainElement('[id="schema.name-helper-text"]');
});

it('translates its text given as a message', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('name')->helperText(t('field.helper', [], 'fixtures-forms'))))
        ->toContainElement(Selectors::CONTENT_COLUMN . ' ' . SchemasSelectors::TEXT, 'As on your passport (translated)');
});
