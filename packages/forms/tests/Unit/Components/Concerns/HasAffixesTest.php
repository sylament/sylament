<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Forms\Components\TextInput;
use Sylament\Support\Enums\Size;
use Twig\Markup;

it('has no affixes by default', function (): void {
    $field = TextInput::make('weight');

    expect($field->getPrefixLabel())->toBeNull()
        ->and($field->getSuffixLabel())->toBeNull()
        ->and($field->getPrefixIcon())->toBeNull()
        ->and($field->getSuffixIcon())->toBeNull()
        ->and($field->getPrefixIconColor())->toBeNull()
        ->and($field->getSuffixIconColor())->toBeNull()
        ->and($field->getPrefixActions())->toBe([])
        ->and($field->getSuffixActions())->toBe([])
        ->and($field->isPrefixInline())->toBeFalse()
        ->and($field->isSuffixInline())->toBeFalse();
});

it('keeps literal and closure affix labels', function (): void {
    $markup = new Markup('<b>kg</b>', 'UTF-8');

    expect(TextInput::make('weight')->prefix('€')->getPrefixLabel())->toBe('€')
        ->and(TextInput::make('weight')->suffix($markup)->getSuffixLabel())->toBe($markup)
        ->and(TextInput::make('weight')->prefix(fn (): string => 'kg')->getPrefixLabel())->toBe('kg')
        ->and(TextInput::make('weight')->suffix(fn (): string => 'kg')->getSuffixLabel())->toBe('kg');
});

it('aliases postfix to suffix', function (): void {
    $field = TextInput::make('weight')->postfix('kg', isInline: true);

    expect($field->getSuffixLabel())->toBe('kg')
        ->and($field->isSuffixInline())->toBeTrue();
});

it('keeps literal and closure affix icons', function (): void {
    $markup = new Markup('<svg></svg>', 'UTF-8');

    expect(TextInput::make('weight')->prefixIcon('heroicon-o-pencil')->getPrefixIcon())->toBe('heroicon-o-pencil')
        ->and(TextInput::make('weight')->suffixIcon($markup)->getSuffixIcon())->toBe($markup)
        ->and(TextInput::make('weight')->prefixIcon(fn (): string => 'heroicon-o-star')->getPrefixIcon())->toBe('heroicon-o-star')
        ->and(TextInput::make('weight')->suffixIcon(fn (): string => 'heroicon-o-star')->getSuffixIcon())->toBe('heroicon-o-star');
});

it('keeps literal and closure affix icon colors', function (): void {
    expect(TextInput::make('weight')->prefixIconColor('danger')->getPrefixIconColor())->toBe('danger')
        ->and(TextInput::make('weight')->suffixIconColor(['500' => '0, 0, 0'])->getSuffixIconColor())->toBe(['500' => '0, 0, 0'])
        ->and(TextInput::make('weight')->prefixIconColor(fn (): string => 'success')->getPrefixIconColor())->toBe('success')
        ->and(TextInput::make('weight')->suffixIconColor()->getSuffixIconColor())->toBeNull();
});

it('evaluates a closure inline flag', function (): void {
    $field = TextInput::make('weight')
        ->inlinePrefix(fn (): bool => true)
        ->inlineSuffix(fn (): bool => false);

    expect($field->isPrefixInline())->toBeTrue()
        ->and($field->isSuffixInline())->toBeFalse();
});

it('lets the last affix call win over an earlier inline flag', function (): void {
    expect(TextInput::make('weight')->inlinePrefix()->prefix('kg')->isPrefixInline())->toBeFalse()
        ->and(TextInput::make('weight')->inlineSuffix()->suffix('kg')->isSuffixInline())->toBeFalse()
        ->and(TextInput::make('weight')->inlinePrefix()->prefixIcon('heroicon-o-pencil')->isPrefixInline())->toBeFalse()
        ->and(TextInput::make('weight')->inlineSuffix()->suffixAction(Action::make('edit'))->isSuffixInline())->toBeFalse()
        ->and(TextInput::make('weight')->prefix('kg', isInline: true)->isPrefixInline())->toBeTrue();
});

it('appends actions across the plural calls', function (): void {
    $field = TextInput::make('weight')
        ->prefixAction(Action::make('first'))
        ->prefixActions([Action::make('second')])
        ->suffixAction(Action::make('third'))
        ->suffixActions([Action::make('fourth')]);

    expect(array_keys($field->getPrefixActions()))->toBe(['first', 'second'])
        ->and(array_keys($field->getSuffixActions()))->toBe(['third', 'fourth']);
});

it('flattens an action closure returning an array', function (): void {
    $field = TextInput::make('weight')
        ->prefixActions([fn (): array => [Action::make('first'), Action::make('second')]]);

    expect(array_keys($field->getPrefixActions()))->toBe(['first', 'second']);
});

it('contributes nothing for an action closure returning null', function (): void {
    expect(TextInput::make('weight')->suffixActions([fn (): ?Action => null])->getSuffixActions())->toBe([]);
});

it('keys cached actions by name, so a later action of the same name wins', function (): void {
    $second = Action::make('edit')->label('Second');

    $field = TextInput::make('weight')
        ->suffixAction(Action::make('edit')->label('First'))
        ->suffixAction($second);

    expect($field->getSuffixActions())->toHaveCount(1)
        ->and($field->getSuffixActions()['edit'])->toBe($second);
});

it('applies the affix action defaults and the schema component when caching', function (): void {
    $field = TextInput::make('weight')->prefixAction(Action::make('generate'));

    $action = $field->getPrefixActions()['generate'];

    expect($action->getSize())->toBe(Size::Small)
        ->and($action->getTemplate())->toBe(Action::ICON_BUTTON_TEMPLATE)
        ->and($action->getSchemaComponent())->toBe($field);
});

it('lets an explicit size and template beat the affix defaults', function (): void {
    $field = TextInput::make('weight')->prefixAction(Action::make('generate')->size(Size::Large)->link());

    $action = $field->getPrefixActions()['generate'];

    expect($action->getSize())->toBe(Size::Large)
        ->and($action->getTemplate())->toBe(Action::LINK_TEMPLATE);
});

it('caches the actions, evaluating each closure once', function (): void {
    $calls = 0;

    $field = TextInput::make('weight')->prefixActions([function () use (&$calls): Action {
        $calls++;

        return Action::make('generate');
    }]);

    $field->getPrefixActions();
    $field->getPrefixActions();

    expect($calls)->toBe(1);
});

it('rebuilds the cache on demand', function (): void {
    $field = TextInput::make('weight')->prefixAction(Action::make('first'));

    $field->getPrefixActions();
    $field->prefixAction(Action::make('second'));

    expect(array_keys($field->cachePrefixActions()))->toBe(['first', 'second'])
        ->and(array_keys($field->getPrefixActions()))->toBe(['first', 'second']);
});

it('splices the affix actions into the component action cache', function (): void {
    $field = TextInput::make('weight')
        ->prefixAction(Action::make('generate'))
        ->suffixAction(Action::make('clear'))
        ->registerActions([Action::make('registered')]);

    expect(array_keys($field->getActions()))->toBe(['generate', 'clear', 'registered']);
});
