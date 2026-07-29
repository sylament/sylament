<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Utilities;

use Sylament\Schemas\StateCasts\BooleanStateCast;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Components\StateGroup;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;

it('reads state by a path relative to the component', function (): void {
    $host = new StateHostFixture();
    $host->data = ['title' => 'Read me'];

    $field = Schema::make($host)->statePath('data')->components([StateField::make('title')])->getComponents()[0];

    $get = $field->makeGetUtility();

    expect($get('title'))->toBe('Read me');
});

it('reads state by an absolute path', function (): void {
    $host = new StateHostFixture();
    $host->data = ['title' => 'Read me'];

    $field = Schema::make($host)->statePath('data')->components([StateField::make('title')])->getComponents()[0];

    $get = $field->makeGetUtility();

    expect($get('/data.title'))->toBe('Read me')
        ->and($get('data.title', isAbsolute: true))->toBe('Read me');
});

it('returns the cast state of its own component, not the raw bag value', function (): void {
    $host = new StateHostFixture();
    $host->data = ['flag' => 1];

    $field = Schema::make($host)->statePath('data')->components([
        StateField::make('flag')->stateCast(new BooleanStateCast(isStoredAsInt: true)),
    ])->getComponents()[0];

    $get = $field->makeGetUtility();

    expect($get('flag'))->toBe(true);
});

it('resolves a sibling component and returns its cast state', function (): void {
    $host = new StateHostFixture();
    $host->data = ['a' => 'AA', 'flag' => 1];

    $field = Schema::make($host)->statePath('data')->components([
        StateField::make('a'),
        StateField::make('flag')->stateCast(new BooleanStateCast(isStoredAsInt: true)),
    ])->getComponents()[0];

    $get = $field->makeGetUtility();

    expect($get('flag'))->toBe(true);
});

it('resolves a parent-level sibling across a container boundary', function (): void {
    $host = new StateHostFixture();
    $host->data = ['address' => ['city' => 'Prague'], 'country' => 'CZ'];

    $city = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->statePath('address')->components([StateField::make('city')]),
        StateField::make('country'),
    ])->getComponents()[0]->getChildSchema()->getComponents()[0];

    $get = $city->makeGetUtility();

    expect($get('../country'))->toBe('CZ')
        ->and($get('city'))->toBe('Prague');
});

it('resolves down into a nested container by absolute path', function (): void {
    $host = new StateHostFixture();
    $host->data = ['address' => ['city' => 'Prague'], 'country' => 'CZ'];

    $country = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->statePath('address')->components([StateField::make('city')]),
        StateField::make('country'),
    ])->getComponents()[1];

    $get = $country->makeGetUtility();

    expect($get('/data.address.city'))->toBe('Prague');
});

it('falls back to a raw bag read for a path owned by no component', function (): void {
    $host = new StateHostFixture();
    $host->data = ['title' => 'Read me', 'undeclared' => 'raw'];

    $field = Schema::make($host)->statePath('data')->components([StateField::make('title')])->getComponents()[0];

    $get = $field->makeGetUtility();

    expect($get('undeclared'))->toBe('raw');
});

it('self-skips its own child containers by default, falling back to a raw bag read', function (): void {
    $host = new StateHostFixture();
    $host->data = ['address' => ['city' => 1]];

    $group = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->statePath('address')->components([
            StateField::make('city')->stateCast(new BooleanStateCast(isStoredAsInt: true)),
        ]),
    ])->getComponents()[0];

    $get = $group->makeGetUtility();

    expect($get('/data.address.city'))->toBe(1);
});

it('resolves into its own child containers once the skip is turned off', function (): void {
    $host = new StateHostFixture();
    $host->data = ['address' => ['city' => 1]];

    $group = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->statePath('address')->components([
            StateField::make('city')->stateCast(new BooleanStateCast(isStoredAsInt: true)),
        ]),
    ])->getComponents()[0];

    $get = $group->makeGetUtility()->skipComponentsChildContainersWhileSearching(false);

    expect($get('/data.address.city'))->toBe(true);
});

it('accepts the skip toggle by its vendor-exact named argument', function (): void {
    $host = new StateHostFixture();
    $host->data = ['address' => ['city' => 1]];

    $group = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->statePath('address')->components([
            StateField::make('city')->stateCast(new BooleanStateCast(isStoredAsInt: true)),
        ]),
    ])->getComponents()[0];

    $get = $group->makeGetUtility()->skipComponentsChildContainersWhileSearching(condition: false);

    expect($get('/data.address.city'))->toBe(true);
});
