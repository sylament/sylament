<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use Sylament\Schemas\Components\Component;
use Sylament\Support\Component\ViewComponent;

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
