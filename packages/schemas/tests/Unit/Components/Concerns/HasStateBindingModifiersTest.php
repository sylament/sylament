<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;

/**
 * @return array<string, Component>
 */
function bindingModifierFields(): array
{
    $host = new StateHostFixture();

    $components = Schema::make($host)->statePath('data')->components([
        StateField::make('plain'),
        StateField::make('live')->live(),
        StateField::make('blurred')->lazy(),
        StateField::make('debounced')->debounce(250),
    ])->getComponents();

    return [
        'plain' => $components[0],
        'live' => $components[1],
        'blurred' => $components[2],
        'debounced' => $components[3],
    ];
}

it('builds the data-model directive over its own state path', function (): void {
    $fields = bindingModifierFields();

    expect($fields['plain']->getDataModel())->toBe('norender|data.plain')
        ->and($fields['live']->getDataModel())->toBe('data.live')
        ->and($fields['blurred']->getDataModel())->toBe('on(change)|data.blurred')
        ->and($fields['debounced']->getDataModel())->toBe('debounce(250)|data.debounced');
});

it('builds the same directive over a caller-supplied path', function (): void {
    $fields = bindingModifierFields();

    expect($fields['plain']->getDataModel('data.meta.0.key'))->toBe('norender|data.meta.0.key')
        ->and($fields['live']->getDataModel('data.meta.0.key'))->toBe('data.meta.0.key')
        ->and($fields['blurred']->getDataModel('data.meta.0.key'))->toBe('on(change)|data.meta.0.key')
        ->and($fields['debounced']->getDataModel('data.meta.0.key'))->toBe('debounce(250)|data.meta.0.key');
});

it('reads its own path afresh when none is supplied', function (): void {
    $field = Schema::make(new StateHostFixture())->statePath('data')->components([
        StateField::make('title')->live(),
    ])->getComponents()[0];

    expect($field->getDataModel(null))->toBe('data.title');
});
