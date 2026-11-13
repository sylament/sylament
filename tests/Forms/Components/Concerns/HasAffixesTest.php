<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\Testing\TestAction;
use Sylament\Forms\Components\Contracts\HasAffixes;
use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Schemas\CodeForm;
use Sylament\Tests\Infrastructure\ComponentRegistry;

it('renders prefix and suffix labels around the input', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('price')->prefix('€')->suffix('per kg')))
        ->toContainElement(Selectors::PREFIX_LABEL, '€')
        ->toContainElement(Selectors::SUFFIX_LABEL, 'per kg');
});

it('puts an inline prefix inside the border; the last call on a side wins', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('w')->prefix('kg', isInline: true)))
        ->toContainElement(Selectors::INLINE_PREFIX);

    expect($this->renderSchemaComponent(TextInput::make('w')->inlinePrefix()->prefix('kg')))
        ->toContainElement(Selectors::PREFIX_LABEL, 'kg')
        ->not->toContainElement(Selectors::INLINE_PREFIX);
});

it('renders an affix icon in gray unless a colour is set', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('a')->prefixIcon('forms:envelope')->suffixIcon('forms:check')->suffixIconColor('success')))
        ->toContainElement(Selectors::PREFIX_ICON . Selectors::GRAY)
        ->toContainElement(Selectors::SUFFIX_ICON . Selectors::color('success'));
});

it('renders affix actions in order; plural calls append and a closure may return several or none', function (): void {
    $field = TextInput::make('a')
        ->prefixAction(Action::make('one'))
        ->prefixActions([fn (): array => [Action::make('two'), Action::make('three')], fn (): null => null]);

    expect($this->renderSchemaComponent($field))->toContainButtons(Selectors::PREFIX_ACTIONS, ['One', 'Two', 'Three']);
});

it('renders an affix action as a small icon button unless the action says otherwise', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('a')->suffixActions([Action::make('copy'), Action::make('open')->link()])))
        ->toContainButtons(Selectors::SUFFIX_ACTIONS . Selectors::SMALL_ICON_BUTTON, ['Copy'])
        ->toContainButtons(Selectors::SUFFIX_ACTIONS . Selectors::LINK, ['Open']);
});

it('runs an affix action when it is clicked', function (): void {
    $this->createLiveComponent(CodeForm::class)
        ->fillForm(['code' => 'X1'])
        ->callAction(TestAction::make('clear')->schemaComponent('code'))
        ->assertFormSet(['code' => null]);
});

it('renders the prefix on every field that offers one', function (string $field): void {
    expect($this->renderSchemaComponent($field::make('a')->prefix('€')))->toContainElement(Selectors::PREFIX_LABEL, '€');
})->with(fn (): array => ComponentRegistry::componentsImplementing(HasAffixes::class));
