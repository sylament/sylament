<?php

declare(strict_types=1);

use Sylament\Forms\Components\ViewField;
use Sylament\Tests\Fixtures\Forms\BareViewField;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Forms\ViewFieldForm;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('renders the template it is given, with the data it is given', function (): void {
    expect($this->renderSchemaComponent(ViewField::make('bio')->template('@Fixtures/forms/WrappedViewField.html.twig')->templateData(['note' => 'Keep it short.'])))
        ->toContainElement(SupportSelectors::INPUT_WRAPPER . ' span', 'Keep it short.');
});

it('gives the template the field as component', function (): void {
    expect($this->createLiveComponent(ViewFieldForm::class, ['data' => ['bio' => 'Hello']])->render()->crawler())
        ->toContainElement('input' . Selectors::INPUT . '[id="form.bio"][value="Hello"]' . SchemasSelectors::boundTo('data.bio'));
});

it('renders only what its template renders', function (): void {
    $wrapped = $this->renderSchemaComponent(ViewField::make('bio')->template('@Fixtures/forms/WrappedViewField.html.twig')->helperText('A line or two.'));
    $unwrapped = $this->renderSchemaComponent(ViewField::make('bio')->template('@Fixtures/forms/UnwrappedViewField.html.twig')->helperText('A line or two.'));

    expect($wrapped)
        ->toContainElement(Selectors::FIELD_WRAPPER . ' label[for="schema.bio"]', 'Bio')
        ->toContainElement(Selectors::FIELD_WRAPPER . ' ' . SchemasSelectors::TEXT, 'A line or two.')
        ->and($unwrapped)
        ->toContainElement('input' . Selectors::INPUT . '[id="schema.bio"]:not([data-model])')
        ->and($unwrapped->filter(Selectors::FIELD_WRAPPER . ', label, ' . SchemasSelectors::TEXT))->toHaveCount(0);
});

it('shows the errors of its field through the wrapper its template opens', function (): void {
    expect($this->createLiveComponent(ViewFieldForm::class)->call('save')->render()->crawler())
        ->toContainElement(Selectors::ERROR_MESSAGE, 'This value should not be blank.');
});

it('refuses to render without a template, naming its class and the fix', function (string $class): void {
    expect(fn (): mixed => $this->renderViewComponent($class::make('bio')))
        ->toThrow(LogicException::class, "The [{$class}] field has no template. Call template() on it, or declare a \$template property on a subclass.");
})->with([
    'ViewField' => [ViewField::class],
    'a subclass' => [BareViewField::class],
]);
