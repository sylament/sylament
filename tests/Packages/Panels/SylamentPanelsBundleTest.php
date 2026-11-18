<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Panel;
use Sylament\Panels\Resources\RelationManagers\RelationGroup;
use Sylament\Panels\SylamentPanelsBundle;
use Sylament\Panels\Twig\Components\GlobalSearch;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

it('registers the panel, the dashboard, the relation group, every navigation object and every Twig component as services, so make() builds each through the container', function (): void {
    $root = dirname(__DIR__, 3) . '/packages/panels/src';
    $classes = [Panel::class, Dashboard::class, RelationGroup::class];

    foreach ([...glob("{$root}/Navigation/*.php") ?: [], ...glob("{$root}/Twig/Components/*.php") ?: []] as $path) {
        $classes[] = 'Sylament\Panels\\' . str_replace('/', '\\', mb_substr($path, mb_strlen($root) + 1, -4));
    }

    expect($classes)->toContain(NavigationItem::class, GlobalSearch::class)
        ->and(array_values(array_filter($classes, fn (string $class): bool => !$this::getContainer()->has($class))))->toBe([]);
});

it('prepends the built assets into AssetMapper only when FrameworkBundle ships it, where AssetMapper finds every controller its manifest names and the live-host helper they import', function (): void {
    $extension = new SylamentPanelsBundle()->getContainerExtension();
    assert($extension instanceof PrependExtensionInterface);

    $withAssetMapper = new ContainerBuilder(new ParameterBag(['kernel.bundles_metadata' => [
        'FrameworkBundle' => ['path' => dirname((string) new ReflectionClass(FrameworkBundle::class)->getFileName()), 'namespace' => 'Symfony\Bundle\FrameworkBundle'],
    ]]));
    $withoutFrameworkBundle = new ContainerBuilder(new ParameterBag(['kernel.bundles_metadata' => []]));

    $extension->prepend($withAssetMapper);
    $extension->prepend($withoutFrameworkBundle);

    $paths = $withAssetMapper->getExtensionConfig('framework')[0]['asset_mapper']['paths'] ?? [];
    $workspace = dirname(__DIR__, 3) . '/packages/panels/assets';
    $manifest = json_decode((string) file_get_contents("{$workspace}/package.json"), true, flags: JSON_THROW_ON_ERROR);
    $assets = $this::getContainer()->get(AssetMapperInterface::class);

    expect(array_values($paths))->toBe(['@sylament/panels'])
        ->and(realpath((string) array_key_first($paths)))->toBe("{$workspace}/dist")
        ->and($withoutFrameworkBundle->getExtensionConfig('framework'))->toBe([])
        ->and(array_map(
            static fn (array $controller): ?string => $assets->getAssetFromSourcePath("{$workspace}/{$controller['main']}")?->logicalPath,
            $manifest['symfony']['controllers'],
        ))->toBe([
            'global-search' => '@sylament/panels/controllers/global_search_controller.js',
            'sidebar' => '@sylament/panels/controllers/sidebar_controller.js',
            'theme' => '@sylament/panels/controllers/theme_controller.js',
            'unsaved-changes' => '@sylament/panels/controllers/unsaved_changes_controller.js',
        ])
        ->and($assets->getAssetFromSourcePath("{$workspace}/dist/utils/live_host.js")?->logicalPath)->toBe('@sylament/panels/utils/live_host.js');
});
