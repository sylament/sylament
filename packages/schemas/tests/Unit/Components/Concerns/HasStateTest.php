<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\Tests\Fixtures\Components\DefaultCastField;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Schemas\Tests\Fixtures\State\Casts\AppendStateCast;

it('composes the absolute state path through the container', function (): void {
    $host = new StateHostFixture();
    $field = Schema::make($host)->statePath('data')->components([StateField::make('title')])->getComponents()[0];

    expect($field->getStatePath())->toBe('data.title')
        ->and($field->getStatePath(isAbsolute: false))->toBe('title');
});

it('composes a nested absolute state path', function (): void {
    $host = new StateHostFixture();
    $field = Schema::make($host)->statePath('data')->components([StateField::make('address.city')])->getComponents()[0];

    expect($field->getStatePath())->toBe('data.address.city');
});

it('reads its raw and cast state from the bag at its path', function (): void {
    $host = new StateHostFixture();
    $host->data = ['title' => 'Hello'];

    $field = Schema::make($host)->statePath('data')->components([StateField::make('title')])->getComponents()[0];

    expect($field->getRawState())->toBe('Hello')
        ->and($field->getState())->toBe('Hello');
});

it('applies a default when the schema is filled with null', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([StateField::make('title')->default('D')]);
    $schema->fill();

    expect($schema->getComponents()[0]->getState())->toBe('D')
        ->and($host->data['title'])->toBe('D');
});

it('fires afterStateHydrated during fill with injected parameters', function (): void {
    $host = new StateHostFixture();
    $received = null;
    $schema = Schema::make($host)->statePath('data')->components([
        StateField::make('title')->afterStateHydrated(function ($state, $component) use (&$received): void {
            $received = ['state' => $state, 'component' => $component];
        }),
    ]);

    $schema->fill(['title' => 'Hydrated']);

    expect($received)->not->toBeNull()
        ->and($received['state'])->toBe('Hydrated')
        ->and($received['component'])->toBeInstanceOf(StateField::class);
});

it('reflects dehydrated(false) in isDehydrated()', function (): void {
    $field = StateField::make('title');

    expect($field->isDehydrated())->toBeTrue()
        ->and($field->dehydrated(false)->isDehydrated())->toBeFalse();
});

it('resolves relative, absolute and parent-walked state paths', function (): void {
    $host = new StateHostFixture();
    $field = Schema::make($host)->statePath('data')->components([StateField::make('title')])->getComponents()[0];

    expect($field->resolveRelativeStatePath('subtitle'))->toBe('data.subtitle')
        ->and($field->resolveRelativeStatePath('/data.title'))->toBe('data.title')
        ->and($field->resolveRelativeStatePath('../sibling'))->toBe('sibling');
});

it('round-trips a state cast: set on hydrate, get on read', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateField::make('flag')->stateCast(new BooleanStateCast(isStoredAsInt: true))->default(true),
    ]);

    $schema->fill();

    expect($host->data['flag'])->toBe(1)
        ->and($schema->getComponents()[0]->getState())->toBeTrue();
});

it('composes default state casts before custom ones', function (): void {
    $host = new StateHostFixture();
    $host->data = ['x' => 'v'];

    $field = Schema::make($host)->statePath('data')->components([
        DefaultCastField::make('x')->stateCast(new AppendStateCast('custom')),
    ])->getComponents()[0];

    expect($field->getState())->toBe('v>default>custom');
});

it('applies state casts once across components sharing a state path', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateField::make('flag')->stateCast(new AppendStateCast('X')),
        StateField::make('flag')->stateCast(new AppendStateCast('X')),
    ]);

    $schema->fill(['flag' => 'v']);

    expect($host->data['flag'])->toBe('v<X');
});

it('returns the cast old state, leaving raw old state untouched', function (): void {
    $host = new StateHostFixture();
    $host->oldState = ['data.flag' => 0];

    $field = Schema::make($host)->statePath('data')->components([
        StateField::make('flag')->stateCast(new BooleanStateCast(isStoredAsInt: true)),
    ])->getComponents()[0];

    expect($field->getOldState())->toBeFalse()
        ->and($field->getOldRawState())->toBe(0);
});

it('reports saved(false) as not dehydrated', function (): void {
    expect(StateField::make('title')->isSaved())->toBeTrue()
        ->and(StateField::make('title')->saved(false)->isDehydrated())->toBeFalse()
        ->and(StateField::make('title')->saved(false)->isSaved())->toBeFalse();
});

it('drops a hidden component from dehydration unless dehydratedWhenHidden', function (): void {
    expect(StateField::make('title')->hidden()->isDehydrated())->toBeFalse()
        ->and(StateField::make('title')->hidden()->dehydratedWhenHidden()->isDehydrated())->toBeTrue();
});

it('applies casts then dehydrateStateUsing in getStateToDehydrate', function (): void {
    $host = new StateHostFixture();
    $field = Schema::make($host)->statePath('data')->components([
        StateField::make('flag')
            ->stateCast(new BooleanStateCast(isStoredAsInt: true))
            ->dehydrateStateUsing(fn ($state): string => $state ? 'on' : 'off'),
    ])->getComponents()[0];

    expect($field->getStateToDehydrate(1))->toBe(['data.flag' => 'on']);
});
