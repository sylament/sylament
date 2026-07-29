<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Utilities;

use Sylament\Schemas\StateCasts\BooleanStateCast;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Components\StateGroup;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;

it('writes state by a path relative to the component', function (): void {
    $host = new StateHostFixture();
    $field = Schema::make($host)->statePath('data')->components([StateField::make('title')])->getComponents()[0];

    $set = $field->makeSetUtility();
    $get = $field->makeGetUtility();

    $returned = $set('title', 'Bye');

    expect($returned)->toBe('Bye')
        ->and($host->data['title'])->toBe('Bye')
        ->and($get('title'))->toBe('Bye');
});

it('evaluates a closure value before writing it', function (): void {
    $host = new StateHostFixture();
    $field = Schema::make($host)->statePath('data')->components([StateField::make('title')])->getComponents()[0];

    $set = $field->makeSetUtility();

    $set('title', fn (): string => 'from closure');

    expect($host->data['title'])->toBe('from closure');
});

it('returns the evaluated closure value', function (): void {
    $host = new StateHostFixture();
    $field = Schema::make($host)->statePath('data')->components([StateField::make('title')])->getComponents()[0];

    $set = $field->makeSetUtility();

    expect($set('title', fn (): string => 'computed'))->toBe('computed');
});

it('applies the target component cast when writing', function (): void {
    $host = new StateHostFixture();
    $field = Schema::make($host)->statePath('data')->components([
        StateField::make('flag')->stateCast(new BooleanStateCast(isStoredAsInt: true)),
    ])->getComponents()[0];

    $set = $field->makeSetUtility();

    $set('flag', true);

    expect($host->data['flag'])->toBe(1);
});

it('resolves and writes a parent-level sibling across a container boundary', function (): void {
    $host = new StateHostFixture();
    $host->data = ['address' => ['city' => 'Prague'], 'country' => 'CZ'];

    $city = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->statePath('address')->components([StateField::make('city')]),
        StateField::make('country'),
    ])->getComponents()[0]->getChildSchema()->getComponents()[0];

    $set = $city->makeSetUtility();

    $set('../country', 'DE');

    expect($host->data['country'])->toBe('DE');
});

it('fires the target component afterStateUpdated hooks when asked', function (): void {
    $host = new StateHostFixture();

    $fired = 0;
    $field = Schema::make($host)->statePath('data')->components([
        StateField::make('title')->afterStateUpdated(function () use (&$fired): void {
            $fired++;
        }),
    ])->getComponents()[0];

    $set = $field->makeSetUtility();

    $set('title', 'new', shouldCallUpdatedHooks: true);

    expect($fired)->toBe(1)
        ->and($host->data['title'])->toBe('new');
});

it('does not fire afterStateUpdated hooks by default', function (): void {
    $host = new StateHostFixture();

    $fired = 0;
    $field = Schema::make($host)->statePath('data')->components([
        StateField::make('title')->afterStateUpdated(function () use (&$fired): void {
            $fired++;
        }),
    ])->getComponents()[0];

    $set = $field->makeSetUtility();

    $set('title', 'new');

    expect($fired)->toBe(0);
});

it('writes into its own child containers through the component state path once the skip is turned off', function (): void {
    $host = new StateHostFixture();
    $host->data = ['address' => ['city' => 'Prague']];

    $fired = 0;
    $group = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->statePath('address')->components([
            StateField::make('city')->afterStateUpdated(function () use (&$fired): void {
                $fired++;
            }),
        ]),
    ])->getComponents()[0];

    $set = $group->makeSetUtility()->skipComponentsChildContainersWhileSearching(false);

    $set('/data.address.city', 'Brno', shouldCallUpdatedHooks: true);

    expect($fired)->toBe(1)
        ->and($host->data['address']['city'])->toBe('Brno');
});

it('accepts the skip toggle by its vendor-exact named argument', function (): void {
    $host = new StateHostFixture();
    $host->data = ['address' => ['city' => 'Prague']];

    $group = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->statePath('address')->components([StateField::make('city')]),
    ])->getComponents()[0];

    $set = $group->makeSetUtility()->skipComponentsChildContainersWhileSearching(condition: false);

    $set('/data.address.city', 'Brno');

    expect($host->data['address']['city'])->toBe('Brno');
});
