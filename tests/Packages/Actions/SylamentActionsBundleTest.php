<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\DeleteBulkAction;
use Sylament\Actions\SylamentActionsBundle;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

it('maps @SylamentActions to the package templates with no twig path configured, so an action renders its trigger partial', function (): void {
    $paths = $this::getContainer()->get('twig.loader.native_filesystem')->getPaths('SylamentActions');

    expect(array_map(realpath(...), $paths))->toBe([dirname(__DIR__, 3) . '/packages/actions/templates'])
        ->and($this->renderViewComponent(Action::make('save')))->toContainButtons(Selectors::TRIGGER, ['Save']);
});

it('registers every action and action group at the package root as a service, so make() builds each through the container', function (): void {
    $classes = [];

    foreach (glob(dirname(__DIR__, 3) . '/packages/actions/src/*.php') ?: [] as $path) {
        $class = 'Sylament\Actions\\' . basename($path, '.php');
        $reflection = new ReflectionClass($class);

        if (!$reflection->isAbstract() && (($class === Action::class) || ($class === ActionGroup::class) || $reflection->isSubclassOf(Action::class) || $reflection->isSubclassOf(ActionGroup::class))) {
            $classes[] = $class;
        }
    }

    expect($classes)->toContain(Action::class, ActionGroup::class, DeleteBulkAction::class)
        ->and(array_values(array_filter($classes, fn (string $class): bool => !$this::getContainer()->has($class))))->toBe([]);
});

it('prepends the built assets into AssetMapper only when FrameworkBundle ships it, where AssetMapper finds the controller its manifest names', function (): void {
    $extension = new SylamentActionsBundle()->getContainerExtension();
    assert($extension instanceof PrependExtensionInterface);

    $withAssetMapper = new ContainerBuilder(new ParameterBag(['kernel.bundles_metadata' => [
        'FrameworkBundle' => ['path' => dirname((string) new ReflectionClass(FrameworkBundle::class)->getFileName()), 'namespace' => 'Symfony\Bundle\FrameworkBundle'],
    ]]));
    $withoutFrameworkBundle = new ContainerBuilder(new ParameterBag(['kernel.bundles_metadata' => []]));

    $extension->prepend($withAssetMapper);
    $extension->prepend($withoutFrameworkBundle);

    $paths = $withAssetMapper->getExtensionConfig('framework')[0]['asset_mapper']['paths'] ?? [];
    $workspace = dirname(__DIR__, 3) . '/packages/actions/assets';
    $manifest = json_decode((string) file_get_contents("{$workspace}/package.json"), true, flags: JSON_THROW_ON_ERROR);

    expect(array_values($paths))->toBe(['@sylament/actions'])
        ->and(realpath((string) array_key_first($paths)))->toBe("{$workspace}/dist")
        ->and($withoutFrameworkBundle->getExtensionConfig('framework'))->toBe([])
        ->and($this::getContainer()->get(AssetMapperInterface::class)->getAssetFromSourcePath("{$workspace}/{$manifest['symfony']['controllers']['dispatch']['main']}")?->logicalPath)
        ->toBe('@sylament/actions/controllers/dispatch_controller.js');
});
