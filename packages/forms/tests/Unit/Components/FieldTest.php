<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components;

use Sylament\Forms\Components\Field;
use Sylament\Forms\Tests\Fixtures\Components\FixtureField;
use Sylament\Schemas\Components\Icon;
use Sylament\Schemas\Components\Text;

use function Sylament\Forms\Tests\wireField;

it('seeds the name and state path from make', function (): void {
    $field = FixtureField::make('title');

    expect($field->getName())->toBe('title')
        ->and($field->getStatePath(isAbsolute: false))->toBe('title');
});

it('has a null name and state path when made without a name', function (): void {
    $field = FixtureField::make();

    expect($field->getName())->toBeNull()
        ->and($field->getStatePath(isAbsolute: false))->toBeNull();
});

it('humanizes the name into a default label', function (string $name, string $label): void {
    expect(FixtureField::make($name)->getLabel())->toBe($label);
})->with([
    'single word' => ['title', 'Title'],
    'underscored' => ['first_name', 'First name'],
    'camel-cased' => ['firstName', 'First name'],
    'dotted path' => ['user.first_name', 'First name'],
]);

it('prefers an explicit label over the humanized default', function (): void {
    expect(FixtureField::make('title')->label('Custom')->getLabel())->toBe('Custom');
});

it('has a null label when both the name and the label are null', function (): void {
    expect(FixtureField::make()->getLabel())->toBeNull();
});

it('is not marked as required by default', function (): void {
    expect(FixtureField::make('title')->isMarkedAsRequired())->toBeFalse();
});

it('marks the field as required', function (): void {
    expect(FixtureField::make('title')->markAsRequired()->isMarkedAsRequired())->toBeTrue()
        ->and(FixtureField::make('title')->markAsRequired(false)->isMarkedAsRequired())->toBeFalse()
        ->and(FixtureField::make('title')->markAsRequired(fn (): bool => true)->isMarkedAsRequired())->toBeTrue();
});

it('marks as required from the validation state when no explicit marker is set', function (): void {
    expect(FixtureField::make('title')->required()->isMarkedAsRequired())->toBeTrue()
        ->and(FixtureField::make('title')->isMarkedAsRequired())->toBeFalse()
        ->and(FixtureField::make('title')->required()->markAsRequired(false)->isMarkedAsRequired())->toBeFalse();
});

it('seeds the helper text into the below-content slot', function (): void {
    $slot = wireField(FixtureField::make('title')->helperText('Some help'))
        ->getChildSchema(Field::BELOW_CONTENT_SCHEMA_KEY);

    expect($slot)->not->toBeNull();

    $components = $slot->getComponents();

    expect($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(Text::class)
        ->and($components[0]->getContent())->toBe('Some help');
});

it('resolves closure helper text into the below-content slot', function (): void {
    $slot = wireField(FixtureField::make('title')->helperText(fn (): string => 'Lazy help'))
        ->getChildSchema(Field::BELOW_CONTENT_SCHEMA_KEY);

    expect($slot)->not->toBeNull()
        ->and($slot->getComponents()[0]->getContent())->toBe('Lazy help');
});

it('adds no below-content slot without helper text', function (): void {
    expect(wireField(FixtureField::make('title'))->getChildSchema(Field::BELOW_CONTENT_SCHEMA_KEY))->toBeNull();
});

it('has no hint or hint icon by default', function (): void {
    $field = FixtureField::make('title');

    expect($field->hasHint())->toBeFalse()
        ->and($field->hasHintIcon())->toBeFalse()
        ->and($field->getHint())->toBeNull()
        ->and($field->getHintIcon())->toBeNull()
        ->and($field->getHintIconTooltip())->toBeNull()
        ->and($field->getHintColor())->toBeNull();
});

it('round-trips the hint, its icon, tooltip and color', function (): void {
    $field = FixtureField::make('title')
        ->hint('A hint')
        ->hintIcon('heroicon-o-eye', 'A tooltip')
        ->hintColor('primary');

    expect($field->getHint())->toBe('A hint')
        ->and($field->hasHint())->toBeTrue()
        ->and($field->getHintIcon())->toBe('heroicon-o-eye')
        ->and($field->hasHintIcon())->toBeTrue()
        ->and($field->getHintIconTooltip())->toBe('A tooltip')
        ->and($field->getHintColor())->toBe('primary');
});

it('resolves closure hints through evaluate', function (): void {
    $field = FixtureField::make('title')
        ->hint(fn (): string => 'Lazy hint')
        ->hintIcon(fn (): string => 'heroicon-o-star');

    expect($field->getHint())->toBe('Lazy hint')
        ->and($field->getHintIcon())->toBe('heroicon-o-star');
});

it('keeps the hint icon tooltip when the icon is set again without one', function (): void {
    // func_num_args() guards the tooltip: a one-argument hintIcon() must not clobber it.
    $field = FixtureField::make('title')
        ->hintIcon('heroicon-o-eye', 'A tooltip')
        ->hintIcon('heroicon-o-star');

    expect($field->getHintIconTooltip())->toBe('A tooltip');
});

it('seeds the hint text and icon into the after-label slot', function (): void {
    $slot = wireField(FixtureField::make('title')->hint('A hint')->hintIcon('heroicon-o-star'))
        ->getChildSchema(Field::AFTER_LABEL_SCHEMA_KEY);

    expect($slot)->not->toBeNull();

    $components = $slot->getComponents();

    expect($components)->toHaveCount(2)
        ->and($components[0])->toBeInstanceOf(Text::class)
        ->and($components[0]->getContent())->toBe('A hint')
        ->and($components[1])->toBeInstanceOf(Icon::class)
        ->and($components[1]->getIcon())->toBe('heroicon-o-star');
});

it('adds no after-label slot without a hint', function (): void {
    expect(wireField(FixtureField::make('title'))->getChildSchema(Field::AFTER_LABEL_SCHEMA_KEY))->toBeNull();
});

it('round-trips the placeholder', function (): void {
    expect(FixtureField::make('title')->placeholder('Type here')->getPlaceholder())->toBe('Type here')
        ->and(FixtureField::make('title')->placeholder(fn (): string => 'Lazy')->getPlaceholder())->toBe('Lazy')
        ->and(FixtureField::make('title')->getPlaceholder())->toBeNull();
});

it('round-trips the autofocus flag', function (): void {
    expect(FixtureField::make('title')->autofocus()->isAutofocused())->toBeTrue()
        ->and(FixtureField::make('title')->isAutofocused())->toBeFalse()
        ->and(FixtureField::make('title')->autofocus(fn (): bool => true)->isAutofocused())->toBeTrue();
});

it('has no error messages without a live component to read the bag from', function (): void {
    expect(FixtureField::make('title')->getErrorMessages())->toBe([]);
});
