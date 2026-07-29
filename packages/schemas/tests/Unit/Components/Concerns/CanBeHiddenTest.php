<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Components\StateGroup;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Support\Enums\Operation;

it('is visible by default', function (): void {
    $field = StateField::make('title');

    expect($field->isHidden())->toBeFalse()
        ->and($field->isVisible())->toBeTrue();
});

it('hides on a literal condition', function (): void {
    expect(StateField::make('title')->hidden()->isHidden())->toBeTrue()
        ->and(StateField::make('title')->hidden(false)->isHidden())->toBeFalse();
});

it('hides on a false visible() condition', function (): void {
    expect(StateField::make('title')->visible(false)->isHidden())->toBeTrue()
        ->and(StateField::make('title')->visible(false)->isVisible())->toBeFalse();
});

it('evaluates hidden()/visible() closures', function (): void {
    expect(StateField::make('title')->hidden(fn (): bool => true)->isHidden())->toBeTrue()
        ->and(StateField::make('title')->visible(fn (): bool => false)->isHidden())->toBeTrue();
});

it('hides a component when all its child components are hidden', function (): void {
    $group = StateGroup::make()
        ->components([StateField::make('a')->hidden(), StateField::make('b')->hidden()])
        ->hiddenWhenAllChildComponentsHidden();
    Schema::make(new StateHostFixture())->components([$group])->getComponents();

    expect($group->isHidden())->toBeTrue();
});

it('keeps a component visible when any child component is visible', function (): void {
    $group = StateGroup::make()
        ->components([StateField::make('a')->hidden(), StateField::make('b')])
        ->hiddenWhenAllChildComponentsHidden();
    Schema::make(new StateHostFixture())->components([$group])->getComponents();

    expect($group->isHidden())->toBeFalse();
});

it('treats a component with no child components as all-hidden', function (): void {
    $group = StateGroup::make()->hiddenWhenAllChildComponentsHidden();
    Schema::make(new StateHostFixture())->components([$group])->getComponents();

    expect($group->isHidden())->toBeTrue();
});

it('hides on a matching operation string, enum, or array', function (): void {
    $attach = fn (StateField $field): StateField => Schema::make(new StateHostFixture())
        ->operation('edit')
        ->components([$field])
        ->getComponents()[0];

    expect($attach(StateField::make('slug')->hiddenOn('edit'))->isHidden())->toBeTrue()
        ->and($attach(StateField::make('slug')->hiddenOn(Operation::Edit))->isHidden())->toBeTrue()
        ->and($attach(StateField::make('slug')->hiddenOn(['create', 'view']))->isHidden())->toBeFalse();
});

it('hides when the host live component matches a class-string operation', function (): void {
    $field = Schema::make(new StateHostFixture())
        ->operation('edit')
        ->components([StateField::make('slug')->hiddenOn(StateHostFixture::class)])
        ->getComponents()[0];

    expect($field->isHidden())->toBeTrue();
});

it('shows only on a matching visibleOn operation', function (): void {
    $field = Schema::make(new StateHostFixture())
        ->operation('edit')
        ->components([StateField::make('slug')->visibleOn('create')])
        ->getComponents()[0];

    expect($field->isVisible())->toBeFalse();
});
