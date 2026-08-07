<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Forms\Components\Field;
use Sylament\Forms\Tests\Fixtures\Components\FixtureField;
use Sylament\Schemas\Components\Component;
use Sylament\Support\Enums\Alignment;
use Sylament\Support\Enums\Size;

use function Sylament\Forms\Tests\wireField;

it('exposes the ten Filament field-wrapper slot key constants', function (): void {
    expect(Field::ABOVE_LABEL_SCHEMA_KEY)->toBe('above_label')
        ->and(Field::BELOW_LABEL_SCHEMA_KEY)->toBe('below_label')
        ->and(Field::BEFORE_LABEL_SCHEMA_KEY)->toBe('before_label')
        ->and(Field::AFTER_LABEL_SCHEMA_KEY)->toBe('after_label')
        ->and(Field::ABOVE_CONTENT_SCHEMA_KEY)->toBe('above_content')
        ->and(Field::BELOW_CONTENT_SCHEMA_KEY)->toBe('below_content')
        ->and(Field::BEFORE_CONTENT_SCHEMA_KEY)->toBe('before_content')
        ->and(Field::AFTER_CONTENT_SCHEMA_KEY)->toBe('after_content')
        ->and(Field::ABOVE_ERROR_MESSAGE_SCHEMA_KEY)->toBe('above_error_message')
        ->and(Field::BELOW_ERROR_MESSAGE_SCHEMA_KEY)->toBe('below_error_message');
});

it('resolves a slot into an inline, embedded schema holding its components', function (): void {
    $component = new Component();
    $field = wireField(FixtureField::make('title')->aboveLabel([$component]));

    $slot = $field->getChildSchema(Field::ABOVE_LABEL_SCHEMA_KEY);

    expect($slot?->isInline())->toBeTrue()
        ->and($slot?->isEmbeddedInParentComponent())->toBeTrue()
        ->and($slot?->getAlignment())->toBeNull()
        ->and($slot?->getParentComponent())->toBe($field)
        ->and($slot?->getComponents())->toBe([$component]);
});

it('end-aligns the after-label and after-content slots', function (): void {
    $field = wireField(
        FixtureField::make('title')
            ->afterLabel([new Component()])
            ->afterContent([new Component()]),
    );

    expect($field->getChildSchema(Field::AFTER_LABEL_SCHEMA_KEY)?->getAlignment())->toBe(Alignment::End)
        ->and($field->getChildSchema(Field::AFTER_CONTENT_SCHEMA_KEY)?->getAlignment())->toBe(Alignment::End);
});

it('configures the non-aligned slots as inline and embedded without alignment', function (): void {
    $field = wireField(
        FixtureField::make('title')
            ->aboveLabel([new Component()])
            ->belowLabel([new Component()])
            ->beforeLabel([new Component()])
            ->aboveContent([new Component()])
            ->belowContent([new Component()])
            ->beforeContent([new Component()])
            ->aboveErrorMessage([new Component()])
            ->belowErrorMessage([new Component()]),
    );

    foreach ([
        Field::ABOVE_LABEL_SCHEMA_KEY,
        Field::BELOW_LABEL_SCHEMA_KEY,
        Field::BEFORE_LABEL_SCHEMA_KEY,
        Field::ABOVE_CONTENT_SCHEMA_KEY,
        Field::BELOW_CONTENT_SCHEMA_KEY,
        Field::BEFORE_CONTENT_SCHEMA_KEY,
        Field::ABOVE_ERROR_MESSAGE_SCHEMA_KEY,
        Field::BELOW_ERROR_MESSAGE_SCHEMA_KEY,
    ] as $key) {
        $slot = $field->getChildSchema($key);

        expect($slot?->isInline())->toBeTrue()
            ->and($slot?->isEmbeddedInParentComponent())->toBeTrue()
            ->and($slot?->getAlignment())->toBeNull();
    }
});

it('clears a slot when set to null', function (): void {
    $field = wireField(FixtureField::make('title')->aboveLabel([new Component()]));
    expect($field->getChildSchema(Field::ABOVE_LABEL_SCHEMA_KEY))->not->toBeNull();

    $field->aboveLabel(null);
    expect($field->getChildSchema(Field::ABOVE_LABEL_SCHEMA_KEY))->toBeNull();
});

it('passes the field to a closure slot as $component', function (): void {
    $received = null;
    $field = wireField(FixtureField::make('title')->aboveContent(function (Field $component) use (&$received): array {
        $received = $component;

        return [new Component()];
    }));

    $field->getChildSchema(Field::ABOVE_CONTENT_SCHEMA_KEY);

    expect($received)->toBe($field);
});

it('defaults a slot action to a small link', function (): void {
    $slot = wireField(FixtureField::make('title')->afterLabel([Action::make('edit')]))
        ->getChildSchema(Field::AFTER_LABEL_SCHEMA_KEY);

    expect($slot)->not->toBeNull();

    $components = $slot->getComponents();

    expect($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(Action::class)
        ->and($components[0]->getTemplate())->toBe(Action::LINK_TEMPLATE)
        ->and($components[0]->getSize())->toBe(Size::Small);
});

it('lets a slot action override the defaults', function (): void {
    $slot = wireField(FixtureField::make('title')->afterLabel([Action::make('edit')->button()->size(Size::Large)]))
        ->getChildSchema(Field::AFTER_LABEL_SCHEMA_KEY);

    $components = $slot->getComponents();

    expect($components[0]->getTemplate())->toBe(Action::BUTTON_TEMPLATE)
        ->and($components[0]->getSize())->toBe(Size::Large);
});

it('defaults a slot action group to a small size', function (): void {
    $slot = wireField(FixtureField::make('title')->afterLabel([ActionGroup::make([Action::make('edit')])]))
        ->getChildSchema(Field::AFTER_LABEL_SCHEMA_KEY);

    $components = $slot->getComponents();

    expect($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(ActionGroup::class)
        ->and($components[0]->getSize())->toBe(Size::Small);
});
