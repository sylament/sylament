<?php

declare(strict_types=1);

use Sylament\Support\SylamentSupportBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

it('prepends the built assets into AssetMapper only when FrameworkBundle ships it', function (): void {
    $extension = new SylamentSupportBundle()->getContainerExtension();
    assert($extension instanceof PrependExtensionInterface);

    $withAssetMapper = new ContainerBuilder(new ParameterBag(['kernel.bundles_metadata' => [
        'FrameworkBundle' => ['path' => dirname((string) new ReflectionClass(FrameworkBundle::class)->getFileName()), 'namespace' => 'Symfony\Bundle\FrameworkBundle'],
    ]]));
    $withoutFrameworkBundle = new ContainerBuilder(new ParameterBag(['kernel.bundles_metadata' => []]));

    $extension->prepend($withAssetMapper);
    $extension->prepend($withoutFrameworkBundle);

    $paths = $withAssetMapper->getExtensionConfig('framework')[0]['asset_mapper']['paths'] ?? [];

    expect(array_values($paths))->toBe(['@sylament/support'])
        ->and(realpath((string) array_key_first($paths)))->toBe(dirname(__DIR__, 3) . '/packages/support/assets/dist')
        ->and($withoutFrameworkBundle->getExtensionConfig('framework'))->toBe([]);
});
