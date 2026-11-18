<?php

declare(strict_types=1);

use Sylament\Tables\Colors\Components\IconColumnComponent\IconComponent;
use Sylament\Tables\Colors\Components\TextColumnComponent\ItemComponent;
use Sylament\Tables\Colors\Components\TextColumnComponent\ItemComponent\IconComponent as ItemIconComponent;
use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Filters\BaseFilter;
use Sylament\Tables\Filters\FilterIndicator;
use Sylament\Tables\Filters\Indicator;
use Sylament\Tables\Grouping\Group;
use Sylament\Tables\SylamentTablesBundle;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Tables\ProductTable;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

it('maps @SylamentTables to the package templates with no twig path configured, so a table renders its partial', function (): void {
    $this->seed($lamp = new Product('Lamp'));

    $paths = $this::getContainer()->get('twig.loader.native_filesystem')->getPaths('SylamentTables');

    expect(array_map(realpath(...), $paths))->toBe([dirname(__DIR__, 3) . '/packages/tables/templates']);

    $this->createLiveComponent(ProductTable::class)->assertCanSeeTableRecords([$lamp]);
});

it('registers every column, summarizer, filter and group at each level its globs name, and the table, so make() builds each through the container', function (): void {
    $root = dirname(__DIR__, 3) . '/packages/tables/src';
    $classes = [Table::class];

    foreach ([...glob("{$root}/Columns/*.php") ?: [], ...glob("{$root}/Columns/Summarizers/*.php") ?: [], ...glob("{$root}/Filters/*.php") ?: [], ...glob("{$root}/Grouping/*.php") ?: []] as $path) {
        $class = 'Sylament\Tables\\' . str_replace('/', '\\', mb_substr($path, mb_strlen($root) + 1, -4));

        if (($class !== FilterIndicator::class) && !new ReflectionClass($class)->isAbstract()) {
            $classes[] = $class;
        }
    }

    expect($classes)->toContain(TextColumn::class, Sum::class, BaseFilter::class, Indicator::class, Group::class)
        ->and(array_values(array_filter($classes, fn (string $class): bool => !$this::getContainer()->has($class))))->toBe([]);
});

it('registers every colour policy public at both levels they nest to, so ColorManager resolves each by class-string', function (): void {
    $root = dirname(__DIR__, 3) . '/packages/tables/src/Colors/Components';
    $policies = array_map(
        static fn (string $path): string => 'Sylament\Tables\Colors\Components\\' . str_replace('/', '\\', mb_substr($path, mb_strlen($root) + 1, -4)),
        [...glob("{$root}/*/*.php") ?: [], ...glob("{$root}/*/*/*.php") ?: []],
    );

    $container = $this::getContainer()->get('service_container');

    expect($policies)->toContain(IconComponent::class, ItemComponent::class, ItemIconComponent::class)
        ->and(array_values(array_filter($policies, fn (string $policy): bool => !$container->has($policy))))->toBe([]);
});

it('prepends the built assets into AssetMapper only when FrameworkBundle ships it, where AssetMapper finds every controller its manifest names and the live-host helper they import', function (): void {
    $extension = new SylamentTablesBundle()->getContainerExtension();
    assert($extension instanceof PrependExtensionInterface);

    $withAssetMapper = new ContainerBuilder(new ParameterBag(['kernel.bundles_metadata' => [
        'FrameworkBundle' => ['path' => dirname((string) new ReflectionClass(FrameworkBundle::class)->getFileName()), 'namespace' => 'Symfony\Bundle\FrameworkBundle'],
    ]]));
    $withoutFrameworkBundle = new ContainerBuilder(new ParameterBag(['kernel.bundles_metadata' => []]));

    $extension->prepend($withAssetMapper);
    $extension->prepend($withoutFrameworkBundle);

    $paths = $withAssetMapper->getExtensionConfig('framework')[0]['asset_mapper']['paths'] ?? [];
    $workspace = dirname(__DIR__, 3) . '/packages/tables/assets';
    $manifest = json_decode((string) file_get_contents("{$workspace}/package.json"), true, flags: JSON_THROW_ON_ERROR);
    $assets = $this::getContainer()->get(AssetMapperInterface::class);

    expect(array_values($paths))->toBe(['@sylament/tables'])
        ->and(realpath((string) array_key_first($paths)))->toBe("{$workspace}/dist")
        ->and($withoutFrameworkBundle->getExtensionConfig('framework'))->toBe([])
        ->and(array_map(
            static fn (array $controller): ?string => $assets->getAssetFromSourcePath("{$workspace}/{$controller['main']}")?->logicalPath,
            $manifest['symfony']['controllers'],
        ))->toBe([
            'column-manager' => '@sylament/tables/controllers/column_manager_controller.js',
            'editable-cell' => '@sylament/tables/controllers/editable_cell_controller.js',
            'limited-list' => '@sylament/tables/controllers/limited_list_controller.js',
            'table' => '@sylament/tables/controllers/table_controller.js',
        ])
        ->and($assets->getAssetFromSourcePath("{$workspace}/dist/utils/live_host.js")?->logicalPath)->toBe('@sylament/tables/utils/live_host.js');
});
