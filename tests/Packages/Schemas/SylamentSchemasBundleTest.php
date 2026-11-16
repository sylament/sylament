<?php

declare(strict_types=1);

use Sylament\Schemas\Colors\Components\IconComponent;
use Sylament\Schemas\Colors\Components\TextComponent;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Schemas\Components\Wizard\Step;
use Sylament\Schemas\Schema;
use Sylament\Schemas\SylamentSchemasBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

it('registers every component, at each level under Components/, and the schema as services, so make() builds each through the container', function (): void {
    $root = dirname(__DIR__, 3) . '/packages/schemas/src/Components';
    $classes = [Schema::class];
    $directories = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

    foreach ($directories as $file) {
        $relative = mb_substr($file->getPathname(), mb_strlen($root) + 1, -4);

        if (preg_match('#^(Concerns|Contracts)/#', $relative) === 1) {
            continue;
        }

        $class = 'Sylament\Schemas\Components\\' . str_replace('/', '\\', $relative);

        if (!new ReflectionClass($class)->isAbstract()) {
            $classes[] = $class;
        }
    }

    expect($classes)->toContain(Section::class, Tab::class, Step::class)
        ->and(array_values(array_filter($classes, fn (string $class): bool => !$this::getContainer()->has($class))))->toBe([]);
});

it('registers every colour policy public, so ColorManager resolves it by class-string', function (): void {
    $policies = array_map(
        static fn (string $path): string => 'Sylament\Schemas\Colors\Components\\' . basename($path, '.php'),
        glob(dirname(__DIR__, 3) . '/packages/schemas/src/Colors/Components/*.php') ?: [],
    );

    $container = $this::getContainer()->get('service_container');

    expect($policies)->toContain(IconComponent::class, TextComponent::class)
        ->and(array_values(array_filter($policies, fn (string $policy): bool => !$container->has($policy))))->toBe([]);
});

it('prepends the built assets into AssetMapper only when FrameworkBundle ships it', function (): void {
    $extension = new SylamentSchemasBundle()->getContainerExtension();
    assert($extension instanceof PrependExtensionInterface);

    $withAssetMapper = new ContainerBuilder(new ParameterBag(['kernel.bundles_metadata' => [
        'FrameworkBundle' => ['path' => dirname((string) new ReflectionClass(FrameworkBundle::class)->getFileName()), 'namespace' => 'Symfony\Bundle\FrameworkBundle'],
    ]]));
    $withoutFrameworkBundle = new ContainerBuilder(new ParameterBag(['kernel.bundles_metadata' => []]));

    $extension->prepend($withAssetMapper);
    $extension->prepend($withoutFrameworkBundle);

    $paths = $withAssetMapper->getExtensionConfig('framework')[0]['asset_mapper']['paths'] ?? [];

    expect(array_values($paths))->toBe(['@sylament/schemas'])
        ->and(realpath((string) array_key_first($paths)))->toBe(dirname(__DIR__, 3) . '/packages/schemas/assets/dist')
        ->and($withoutFrameworkBundle->getExtensionConfig('framework'))->toBe([]);
});

it('serves its built controllers and the utilities forms imports through AssetMapper as @sylament/schemas', function (): void {
    $assets = $this::getContainer()->get(AssetMapperInterface::class);
    $dist = dirname(__DIR__, 3) . '/packages/schemas/assets/dist';

    expect($assets->getAssetFromSourcePath("{$dist}/controllers/tabs_controller.js")?->logicalPath)->toBe('@sylament/schemas/controllers/tabs_controller.js')
        ->and($assets->getAssetFromSourcePath("{$dist}/utils/schema_component_method.js")?->logicalPath)->toBe('@sylament/schemas/utils/schema_component_method.js');
});
