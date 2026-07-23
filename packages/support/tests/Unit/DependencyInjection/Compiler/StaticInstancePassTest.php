<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\DependencyInjection\Compiler;

use Sylament\Support\DependencyInjection\Compiler\StaticInstancePass;
use Sylament\Support\DependencyInjection\StaticInstanceInterface;
use Sylament\Support\Tests\Fixtures\DependencyInjection\AnotherStaticInstanceFixture;
use Sylament\Support\Tests\Fixtures\DependencyInjection\PlainFixture;
use Sylament\Support\Tests\Fixtures\DependencyInjection\StaticInstanceFixture;
use Sylament\Support\Tests\Fixtures\DependencyInjection\UnloadableServiceFixture;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

it('tags services that implement StaticInstanceInterface', function (): void {
    $container = new ContainerBuilder();
    $container->setDefinition(StaticInstanceFixture::class, new Definition(StaticInstanceFixture::class));
    $container->setDefinition(AnotherStaticInstanceFixture::class, new Definition(AnotherStaticInstanceFixture::class));

    new StaticInstancePass()->process($container);

    expect($container->getDefinition(StaticInstanceFixture::class)->hasTag(StaticInstanceInterface::class))->toBeTrue();
    expect($container->getDefinition(AnotherStaticInstanceFixture::class)->hasTag(StaticInstanceInterface::class))->toBeTrue();
});

it('does not tag services that do not implement StaticInstanceInterface', function (): void {
    $container = new ContainerBuilder();
    $container->setDefinition(PlainFixture::class, new Definition(PlainFixture::class));

    new StaticInstancePass()->process($container);

    expect($container->getDefinition(PlainFixture::class)->hasTag(StaticInstanceInterface::class))->toBeFalse();
});

it('skips definitions without a class', function (): void {
    $container = new ContainerBuilder();
    $container->setDefinition('no_class', new Definition());

    new StaticInstancePass()->process($container);

    expect($container->getDefinition('no_class')->getTags())->toBe([]);
});

it('skips service classes that cannot be autoloaded', function (): void {
    $container = new ContainerBuilder();
    $container->setDefinition('unloadable', new Definition(UnloadableServiceFixture::class));

    new StaticInstancePass()->process($container);

    expect($container->getDefinition('unloadable')->hasTag(StaticInstanceInterface::class))->toBeFalse();
});
