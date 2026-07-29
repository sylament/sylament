<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\StateCasts\BooleanStateCast;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Components\StateGroup;
use Sylament\Schemas\Tests\Fixtures\Concerns\MultiSchemaHostFixture;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;

it('fills the bag and null-fills unset leaves', function (): void {
    $host = new StateHostFixture();

    $host->fill(['title' => 'Hi']);

    expect($host->data)->toBe(['title' => 'Hi', 'subtitle' => null]);
});

it('assembles the dehydrated bag from getState()', function (): void {
    $host = new StateHostFixture();
    $host->fill(['title' => 'Hi', 'subtitle' => 'Yo']);

    expect($host->getSchema()->getState())->toBe(['title' => 'Hi', 'subtitle' => 'Yo']);
});

it('binds multiple schemas on one host to independent state paths', function (): void {
    $host = new MultiSchemaHostFixture();

    $host->getSchema('editForm')->fill(['title' => 'Edit me']);
    $host->getSchema('filterForm')->fill(['search' => 'query']);

    expect($host->editData)->toBe(['title' => 'Edit me'])
        ->and($host->filterData)->toBe(['search' => 'query']);
});

it('drops a component key when dehydrated(false)', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateField::make('title'),
        StateField::make('secret')->dehydrated(false),
    ]);

    $schema->fill(['title' => 'Hi', 'secret' => 'shh']);

    expect($schema->getState())->toBe(['title' => 'Hi']);
});

it('dehydrates cast values as their typed form', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateField::make('flag')->stateCast(new BooleanStateCast(isStoredAsInt: true))->default(true),
    ]);

    $schema->fill();

    expect($host->data['flag'])->toBe(1)
        ->and($schema->getState())->toBe(['flag' => true]);
});

it('caches which components are dehydrated by absolute path', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateField::make('title'),
        StateField::make('secret')->dehydrated(false),
    ]);

    expect($schema->hasDehydratedComponent('data.title'))->toBeTrue()
        ->and($schema->hasDehydratedComponent('data.secret'))->toBeFalse();
});

it('keeps a shared state path when a dehydrated sibling owns it', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateField::make('shared')->dehydrated(false),
        StateField::make('shared'),
    ]);
    $host->data = ['shared' => 'v'];

    expect($schema->getState())->toBe(['shared' => 'v']);
});

it('forgets only its own descendants when a sibling shares the path', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->statePath('shared')->components([StateField::make('a')])->dehydrated(false),
        StateGroup::make()->statePath('shared')->components([StateField::make('b')])->dehydrated(false),
        StateField::make('keep'),
    ]);
    $host->data = ['shared' => ['a' => 'AA', 'b' => 'BB'], 'keep' => 'KK'];

    expect($schema->getState())->toBe(['keep' => 'KK']);
});

it('assembles a nested (layout-wrapped) schema, dropping a non-dehydrated leaf', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->components([
            StateField::make('a'),
            StateField::make('b')->dehydrated(false),
        ]),
    ]);
    $host->data = ['a' => 'AA', 'b' => 'BB'];

    expect($schema->getState())->toBe(['a' => 'AA']);
});

it('mutates dehydrated state through nested schemas', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->components([
            StateField::make('a')->mutateDehydratedStateUsing(fn ($state): string => "[{$state}]"),
        ]),
    ]);
    $host->data = ['a' => 'x'];

    expect($schema->getState())->toBe(['a' => '[x]']);
});

it('fires beforeStateDehydrated and re-syncs mutated state when asked', function (): void {
    $host = new StateHostFixture();
    $fired = false;
    $schema = Schema::make($host)->statePath('data')->components([
        StateField::make('title')->beforeStateDehydrated(function ($component) use (&$fired): void {
            $fired = true;
            $component->state('rewritten');
        }, shouldUpdateValidatedStateAfter: true),
    ]);
    $schema->fill(['title' => 'original']);

    $state = $schema->getState();

    expect($fired)->toBeTrue()
        ->and($state)->toBe(['title' => 'rewritten']);
});
