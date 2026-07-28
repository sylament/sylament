<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;

it('is enabled by default', function (): void {
    $component = new Component()->container(new Schema());

    expect($component->isDisabled())->toBeFalse()
        ->and($component->isEnabled())->toBeTrue();
});

it('disables on a literal condition', function (): void {
    $component = new Component()->container(new Schema())->disabled();

    expect($component->isDisabled())->toBeTrue()
        ->and($component->isEnabled())->toBeFalse();
});

it('accepts a literal false condition', function (): void {
    $component = new Component()->container(new Schema())->disabled(false);

    expect($component->isDisabled())->toBeFalse();
});

it('evaluates a disabled closure with the component injected', function (): void {
    $component = new Component()->container(new Schema());

    $received = null;
    $component->disabled(function (Component $component) use (&$received): bool {
        $received = $component;

        return true;
    });

    expect($component->isDisabled())->toBeTrue()
        ->and($received)->toBe($component);
});

it('inherits disabled from its container schema', function (): void {
    $schema = new Schema()->disabled();
    $component = new Component()->container($schema);

    expect($component->isDisabled())->toBeTrue();
});

it('drops a disabled component from getState() via the saved() coupling', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateField::make('title'),
        StateField::make('secret')->disabled(),
    ]);

    $schema->fill(['title' => 'Hi', 'secret' => 'shh']);

    expect($schema->getState())->toBe(['title' => 'Hi']);
});
