<?php

declare(strict_types=1);

use Sylament\Forms\Components\FusedGroup;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Components\ViewField;
use Sylament\Tests\Fixtures\Forms\Selectors;

describe('label', function (): void {
    it('labels the control it wraps, out of view only when hidden, and renders no label for a field with none', function (): void {
        expect($this->renderSchemaComponent(TextInput::make('email')))
            ->toContainElement('.sy-fo-field-label-col label[for="schema.email"]:not(.sy-sr-only)', 'Email')
            ->toContainElement('input[id="schema.email"]')
            ->and($this->renderSchemaComponent(TextInput::make('email')->hiddenLabel()))
            ->toContainElement('label.sy-sr-only[for="schema.email"]', 'Email')
            ->not->toContainElement('.sy-fo-field-label-col')
            ->and($this->renderSchemaComponent(TextInput::make('email')->name(null)))
            ->toContainElement('input[id="schema.email"]')
            ->not->toContainElement('label');
    });

    it('renders a label prefix and suffix inside the label, marking it as holding an inline control', function (): void {
        $field = static fn (string $prefix, string $suffix): ViewField => ViewField::make('agree')
            ->template('@Fixtures/forms/FieldWrapperTag.html.twig')
            ->templateData(['heading' => false, 'prefix' => $prefix, 'suffix' => $suffix]);

        expect($this->renderSchemaComponent($field('Before', 'After')))
            ->toContainElement('label.sy-fo-field-label-inline-control[for="schema.agree"]', 'Before Agree After')
            ->and($this->renderSchemaComponent($field('', '')))
            ->toContainElement('label[for="schema.agree"]:not(.sy-fo-field-label-inline-control)', 'Agree');
    });
});

describe('labelAsHeading', function (): void {
    it('renders the label as a heading its control can point at, in view and out of it', function (): void {
        $field = static fn (): ViewField => ViewField::make('agree')
            ->template('@Fixtures/forms/FieldWrapperTag.html.twig')
            ->templateData(['heading' => true, 'prefix' => '', 'suffix' => '']);

        expect($this->renderSchemaComponent($field()))
            ->toContainElement('div.sy-fo-field-label[id="schema.agree-label"]:not(.sy-sr-only)', 'Agree')
            ->not->toContainElement('label')
            ->and($this->renderSchemaComponent($field()->hiddenLabel()))
            ->toContainElement('div.sy-fo-field-label.sy-sr-only[id="schema.agree-label"]', 'Agree')
            ->not->toContainElement('label');
    });

    it('gives the heading of a field with no id no id of its own', function (): void {
        expect($this->renderSchemaComponent(FusedGroup::make([TextInput::make('first')])->label('Name')))
            ->toContainElement('div.sy-fo-field-label', 'Name')
            ->not->toContainElement('[id$="-label"]');
    });
});

describe('plain wrapper', function (): void {
    it('wraps a field a fused group holds in its label, out of view, and its control alone', function (): void {
        $render = $this->renderSchemaComponent(FusedGroup::make([
            TextInput::make('first')->required()->helperText('Your given name.')->aboveLabel('Above label'),
        ])->key('name'));

        expect($render)
            ->toContainElement('.sy-fo-fused-group ' . Selectors::FIELD_WRAPPER . ' label.sy-sr-only', 'First')
            ->toContainElement('.sy-fo-fused-group ' . Selectors::FIELD_WRAPPER . ' input[name="first"]')
            ->and($render->filter('.sy-fo-fused-group .sy-fo-field-label-col, .sy-fo-fused-group ' . Selectors::REQUIRED_MARK))->toHaveCount(0)
            ->and($render->filter('.sy-fo-fused-group')->text())->toBe('First');
    });
});

it('marks the root of both wrapper branches as the target a validation error scrolls to', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('first')))
        ->toContainElement(Selectors::FIELD_WRAPPER . ' input[name="first"]')
        ->and($this->renderSchemaComponent(FusedGroup::make([TextInput::make('first')])->key('name')))
        ->toContainElement('.sy-fo-fused-group ' . Selectors::FIELD_WRAPPER . ' input[name="first"]');
});
