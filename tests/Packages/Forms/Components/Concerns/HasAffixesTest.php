<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\Testing\TestAction;
use Sylament\Forms\Components\Contracts\HasAffixes;
use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Forms\AffixActionForm;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Schemas\CodeForm;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Infrastructure\ComponentRegistry;

use function Symfony\Component\Translation\t;

it('renders prefix and suffix labels around the input', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('price')->prefix('€')->suffix('per kg')))
        ->toContainElement(SupportSelectors::PREFIX, '€')
        ->toContainElement(SupportSelectors::SUFFIX, 'per kg');
});

it('puts an inline prefix inside the border; the last call on a side wins', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('w')->prefix('kg', isInline: true)))
        ->toContainElement(SupportSelectors::INLINE_PREFIX);

    expect($this->renderSchemaComponent(TextInput::make('w')->inlinePrefix()->prefix('kg')))
        ->toContainElement(SupportSelectors::PREFIX, 'kg')
        ->not->toContainElement(SupportSelectors::INLINE_PREFIX);
});

it('puts an inline suffix inside the border, and pads the control on an inline side only while that side shows something', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('w')->prefix('kg', isInline: true)->suffix('EUR', isInline: true)))
        ->toContainElement(SupportSelectors::INLINE_SUFFIX, 'EUR')
        ->toContainElement('input' . Selectors::INPUT_HAS_INLINE_PREFIX . Selectors::INPUT_HAS_INLINE_SUFFIX . '[name="w"]')
        ->and($this->renderSchemaComponent(TextInput::make('w')->prefix('kg')->inlinePrefix()->suffix('EUR')->inlineSuffix()))
        ->toContainElement(SupportSelectors::INLINE_PREFIX, 'kg')
        ->toContainElement(SupportSelectors::INLINE_SUFFIX, 'EUR')
        ->and($this->renderSchemaComponent(TextInput::make('w')->prefix('kg')->inlineSuffix()))
        ->toContainElement('input[name="w"]:not(' . Selectors::INPUT_HAS_INLINE_PREFIX . '):not(' . Selectors::INPUT_HAS_INLINE_SUFFIX . ')');
});

it('renders an affix icon in gray unless a colour is set', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('a')->prefixIcon('forms:envelope')->suffixIcon('forms:check')->suffixIconColor('success')))
        ->toContainElement(SupportSelectors::PREFIX_ICON . SupportSelectors::GRAY)
        ->toContainElement(SupportSelectors::SUFFIX_ICON . SupportSelectors::color('success'))
        ->and($this->renderSchemaComponent(TextInput::make('b')->prefixIcon('forms:envelope')->prefixIconColor('danger')))
        ->toContainElement(SupportSelectors::PREFIX_ICON . SupportSelectors::color('danger'));
});

it('renders affix actions in order; plural calls append and a closure may return several or none', function (): void {
    $field = TextInput::make('a')
        ->prefixAction(Action::make('one'))
        ->prefixActions([fn (): array => [Action::make('two'), Action::make('three')], fn (): null => null]);

    expect($this->renderSchemaComponent($field))->toContainButtons(SupportSelectors::PREFIX_ACTIONS, ['One', 'Two', 'Three']);
});

it('renders an affix action as a small icon button unless the action says otherwise', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('a')->suffixActions([Action::make('copy'), Action::make('open')->link()])))
        ->toContainButtons(SupportSelectors::SUFFIX_ACTIONS . SupportSelectors::SMALL_ICON_BUTTON, ['Copy'])
        ->toContainButtons(SupportSelectors::SUFFIX_ACTIONS . SupportSelectors::LINK, ['Open']);
});

it('renders an affix action in its own colour, keyed by its name so a later one of the same name replaces it', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('a')->suffixActions([Action::make('go')->label('First'), Action::make('go')->label('Second')])))
        ->toContainButtons(SupportSelectors::SUFFIX_ACTIONS, ['Second'])
        ->toContainButtons(SupportSelectors::SUFFIX_ACTIONS . SupportSelectors::color('primary'), ['Second']);
});

it('keeps the affix actions it prepared until it is told to prepare them again', function (): void {
    $field = TextInput::make('a')->suffixAction(Action::make('one'));
    $field->getSuffixActions();
    $field->suffixAction(Action::make('two'));

    expect($this->renderSchemaComponent($field))->toContainButtons(SupportSelectors::SUFFIX_ACTIONS, ['One']);

    $field->cacheSuffixActions();

    expect($this->renderSchemaComponent($field))->toContainButtons(SupportSelectors::SUFFIX_ACTIONS, ['One', 'Two']);
});

it('runs an affix action when it is clicked, against the field it sits on', function (): void {
    $this->createLiveComponent(CodeForm::class)
        ->fillForm(['code' => 'X1'])
        ->assertFormSet(['code' => 'X1'])
        ->callAction(TestAction::make('clear')->schemaComponent('code'))
        ->assertFormSet(['code' => null]);

    $this->createLiveComponent(AffixActionForm::class)
        ->fillForm(['code' => 'X1'])
        ->callAction(TestAction::make('echo')->schemaComponent('code'));

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['echo: X1']);
});

it('renders the prefix on every field that offers one', function (string $field): void {
    expect($this->renderSchemaComponent($field::make('a')->prefix('€')))->toContainElement(SupportSelectors::PREFIX, '€');
})->with(fn (): array => ComponentRegistry::componentsImplementing(HasAffixes::class));

it('translates a prefix or suffix given as a message', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('weight')->prefix(t('field.prefix', [], 'fixtures-forms'))->suffix(t('field.prefix', [], 'fixtures-forms'))))
        ->toContainElement(SupportSelectors::PREFIX, 'per kg (translated)')
        ->toContainElement(SupportSelectors::SUFFIX, 'per kg (translated)');
});
