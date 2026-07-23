<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;

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
