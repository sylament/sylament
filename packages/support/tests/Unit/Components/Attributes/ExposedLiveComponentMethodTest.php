<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Components\Attributes;

use Attribute;
use ReflectionClass;
use ReflectionMethod;
use Sylament\Support\Components\Attributes\ExposedLiveComponentMethod;
use Sylament\Support\Tests\Fixtures\Components\Attributes\ExposedLiveComponentMethodFixture;

it('is a method-targeted attribute', function (): void {
    $reflection = new ReflectionClass(ExposedLiveComponentMethod::class);
    $attributes = $reflection->getAttributes(Attribute::class);

    expect($attributes)->toHaveCount(1);

    $instance = $attributes[0]->newInstance();

    expect($instance->flags)->toBe(Attribute::TARGET_METHOD);
});

it('is discoverable on a method that carries it', function (): void {
    $exposed = new ReflectionMethod(ExposedLiveComponentMethodFixture::class, 'exposed');
    $notExposed = new ReflectionMethod(ExposedLiveComponentMethodFixture::class, 'notExposed');

    expect($exposed->getAttributes(ExposedLiveComponentMethod::class))->toHaveCount(1)
        ->and($notExposed->getAttributes(ExposedLiveComponentMethod::class))->toBe([]);
});
