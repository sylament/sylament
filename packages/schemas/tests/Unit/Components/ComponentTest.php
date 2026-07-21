<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use Sylament\Schemas\Components\Component;
use Sylament\Support\Component\ViewComponent;
use Sylament\Support\Service\Invoker;
use Symfony\Component\DependencyInjection\Container;

beforeEach(function (): void {
    Invoker::setInstance(new Invoker(new Container()));
});

afterEach(function (): void {
    Invoker::unsetInstance();
});

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
