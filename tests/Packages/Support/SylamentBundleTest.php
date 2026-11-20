<?php

declare(strict_types=1);

use Sylament\Infolists\SylamentInfolistsBundle;
use Sylament\Support\SylamentBundle;
use Sylament\Support\SylamentSupportBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

it("prepends each bundle's built assets into AssetMapper under its workspace's name only when FrameworkBundle ships it, and nothing for a bundle without a workspace", function (): void {
    $bundles = array_filter($this::$kernel->getBundles(), static fn (object $bundle): bool => $bundle instanceof SylamentBundle);
    $frameworkBundle = ['path' => dirname((string) new ReflectionClass(FrameworkBundle::class)->getFileName()), 'namespace' => 'Symfony\Bundle\FrameworkBundle'];
    $prepended = [];
    $expected = [];

    foreach ($bundles as $name => $bundle) {
        $extension = $bundle->getContainerExtension();
        assert($extension instanceof PrependExtensionInterface);

        $withAssetMapper = new ContainerBuilder(new ParameterBag(['kernel.bundles_metadata' => ['FrameworkBundle' => $frameworkBundle]]));
        $withoutFrameworkBundle = new ContainerBuilder(new ParameterBag(['kernel.bundles_metadata' => []]));

        $extension->prepend($withAssetMapper);
        $extension->prepend($withoutFrameworkBundle);

        $paths = $withAssetMapper->getExtensionConfig('framework')[0]['asset_mapper']['paths'] ?? [];
        $prepended[$name] = [
            'with FrameworkBundle' => array_combine(array_map(realpath(...), array_keys($paths)), $paths),
            'without FrameworkBundle' => $withoutFrameworkBundle->getExtensionConfig('framework'),
        ];

        $workspace = $bundle->getPath() . '/assets';
        $expected[$name] = [
            'with FrameworkBundle' => is_file("{$workspace}/package.json")
                ? [realpath("{$workspace}/dist") => json_decode((string) file_get_contents("{$workspace}/package.json"), true, flags: JSON_THROW_ON_ERROR)['name']]
                : [],
            'without FrameworkBundle' => [],
        ];
    }

    expect(array_map(get_class(...), $bundles))->toContain(SylamentSupportBundle::class, SylamentInfolistsBundle::class)
        ->and($expected['SylamentSupportBundle']['with FrameworkBundle'])->toBe([dirname(__DIR__, 3) . '/packages/support/assets/dist' => '@sylament/support'])
        ->and($expected['SylamentInfolistsBundle']['with FrameworkBundle'])->toBe([])
        ->and($prepended)->toBe($expected);
});
