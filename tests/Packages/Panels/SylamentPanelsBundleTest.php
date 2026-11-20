<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Panel;
use Sylament\Panels\Resources\RelationManagers\RelationGroup;
use Sylament\Panels\Twig\Components\GlobalSearch;
use Symfony\Component\AssetMapper\AssetMapperInterface;

it('registers the panel, the dashboard, the relation group, every navigation object and every Twig component as services, so make() builds each through the container', function (): void {
    $root = dirname(__DIR__, 3) . '/packages/panels/src';
    $classes = [Panel::class, Dashboard::class, RelationGroup::class];

    foreach ([...glob("{$root}/Navigation/*.php") ?: [], ...glob("{$root}/Twig/Components/*.php") ?: []] as $path) {
        $classes[] = 'Sylament\Panels\\' . str_replace('/', '\\', mb_substr($path, mb_strlen($root) + 1, -4));
    }

    expect($classes)->toContain(NavigationItem::class, GlobalSearch::class)
        ->and(array_values(array_filter($classes, fn (string $class): bool => !$this::getContainer()->has($class))))->toBe([]);
});

it('serves every controller its manifest names and the live-host helper they import through AssetMapper as @sylament/panels', function (): void {
    $workspace = dirname(__DIR__, 3) . '/packages/panels/assets';
    $manifest = json_decode((string) file_get_contents("{$workspace}/package.json"), true, flags: JSON_THROW_ON_ERROR);
    $assets = $this::getContainer()->get(AssetMapperInterface::class);

    expect(array_map(
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
