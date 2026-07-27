<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Support\Components\ViewComponent;

it('resolves the $component closure parameter to itself', function (): void {
    $component = new Component();

    $result = $component->evaluate(fn ($component) => $component);

    expect($result)->toBe($component);
});

it('resolves a self-typed closure parameter to itself', function (): void {
    $component = new Component();

    $result = $component->evaluate(fn (Component $instance): Component => $instance);

    expect($result)->toBe($component);
});

it('resolves an ancestor-typed closure parameter to itself', function (): void {
    $component = new Component();

    $result = $component->evaluate(fn (ViewComponent $instance): ViewComponent => $instance);

    expect($result)->toBe($component);
});

it('resolves the $liveComponent closure parameter to the host', function (): void {
    $host = new StateHostFixture();
    $field = Schema::make($host)->statePath('data')->components([StateField::make('title')])->getComponents()[0];

    expect($field->evaluate(fn ($liveComponent) => $liveComponent))->toBe($host);
});

it('resolves $get and $set to working state utilities', function (): void {
    $host = new StateHostFixture();
    $host->data = ['title' => 'x'];

    $field = Schema::make($host)->statePath('data')->components([StateField::make('title')])->getComponents()[0];

    $result = $field->evaluate(function ($get, $set) {
        $set('title', 'y');

        return $get('title');
    });

    expect($result)->toBe('y')
        ->and($host->data['title'])->toBe('y');
});

it('resolves $state and $rawState to the current state', function (): void {
    $host = new StateHostFixture();
    $host->data = ['title' => 'now'];

    $field = Schema::make($host)->statePath('data')->components([StateField::make('title')])->getComponents()[0];

    expect($field->evaluate(fn ($state) => $state))->toBe('now')
        ->and($field->evaluate(fn ($rawState) => $rawState))->toBe('now');
});
