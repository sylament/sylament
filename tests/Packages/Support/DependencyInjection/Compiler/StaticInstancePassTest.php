<?php

declare(strict_types=1);

use Sylament\Support\Configuration\ComponentManager;
use Sylament\Support\DependencyInjection\Compiler\StaticInstancePass;
use Sylament\Support\DependencyInjection\Contracts\StaticInstance;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

it('tags each StaticInstance definition, skipping one whose class cannot load', function (): void {
    $container = new ContainerBuilder();
    $container->register('manager', ComponentManager::class);
    $container->register('plain', DateTimeImmutable::class);
    $container->register('form.live_collection', LiveCollectionType::class);

    new StaticInstancePass()->process($container);

    expect(fn (): bool => class_exists(LiveCollectionType::class))->toThrow(Error::class, 'Class "Symfony\Component\Form\AbstractType" not found')
        ->and(array_keys($container->findTaggedServiceIds(StaticInstance::class)))->toBe(['manager']);
});
