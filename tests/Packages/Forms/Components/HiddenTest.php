<?php

declare(strict_types=1);

use Sylament\Forms\Components\Hidden;
use Sylament\Tests\Fixtures\Forms\HiddenStateForm;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('renders a single hidden input bound to its state, carrying its id and no name', function (): void {
    $render = $this->renderSchemaComponent(Hidden::make('token'));

    expect($render->filter('input'))->toHaveCount(1)
        ->and($render)->toContainElement('input[type="hidden"][id="schema.token"]' . SchemasSelectors::boundTo('data.token') . ':not([name])');
});

it('collapses the schema slot it sits in', function (): void {
    expect($this->renderSchemaComponent(Hidden::make('token')))
        ->toContainElement('.sy-grid-col' . SupportSelectors::HIDDEN . ' input[type="hidden"]');
});

it('renders no field wrapper, whatever label, helper text or hint it is given', function (): void {
    $render = $this->renderSchemaComponent(Hidden::make('token')->label('Token')->helperText('Kept secret.')->hint('Rotates daily.'));

    expect($render)->toContainElement('input[type="hidden"]')
        ->and($render->filter(Selectors::FIELD_WRAPPER . ', label'))->toHaveCount(0)
        ->and($render->text())->toBe('');
});

it('puts extraAttributes() on the input', function (): void {
    expect($this->renderSchemaComponent(Hidden::make('token')->extraAttributes(['data-purpose' => 'csrf'])))
        ->toContainElement('input[type="hidden"][data-purpose="csrf"]');
});

it('renders its state into value, an empty string when unset', function (): void {
    expect($this->createLiveComponent(HiddenStateForm::class, ['data' => ['token' => 'abc']])->render()->crawler())
        ->toContainElement('input[type="hidden"][value="abc"]')
        ->and($this->createLiveComponent(HiddenStateForm::class)->render()->crawler())
        ->toContainElement('input[type="hidden"][value=""]');
});

it('takes a client-written value like any field, and validates it', function (): void {
    $this->createLiveComponent(HiddenStateForm::class)
        ->fillForm(['token' => 'x'])
        ->assertFormSet(['token' => 'x'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->fillForm(['token' => ''])
        ->call('save')
        ->assertHasFormErrors(['token']);
});
