<?php

declare(strict_types=1);

namespace Sylament\Support;

use RuntimeException;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Components\Component;
use Sylament\Support\Configuration\ComponentManager;
use Sylament\Support\DependencyInjection\Compiler\ComponentResolverPass;
use Sylament\Support\DependencyInjection\Compiler\StaticInstancePass;
use Sylament\Support\DependencyInjection\ComponentResolver;
use Sylament\Support\DependencyInjection\Contracts\StaticInstance;
use Sylament\Support\DependencyInjection\StaticInstanceRegistry;
use Sylament\Support\Evaluation\Invoker;
use Sylament\Support\Icons\IconManager;
use Sylament\Support\Twig\Extension\AttributeExtension;
use Sylament\Support\Twig\Extension\ColorExtension;
use Sylament\Support\Twig\Extension\ComponentExtension;
use Sylament\Support\Twig\Extension\GridExtension;
use Sylament\Support\Twig\Extension\IconExtension;
use Sylament\Support\Twig\Extension\MarkupExtension;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\UX\Icons\IconRendererInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

class SylamentSupportBundle extends SylamentBundle
{
    protected string $extensionAlias = 'sylament';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new StaticInstancePass());
        $container->addCompilerPass(new ComponentResolverPass());

        $container->registerForAutoconfiguration(Component::class)
            ->addTag('sylament.component');
    }

    public function boot(): void
    {
        parent::boot();

        $this->bootStaticInstances();
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('colors')
                    ->info('Color palettes keyed by name, e.g. "primary: \'#7c3aed\'" or an explicit shade map.')
                    ->normalizeKeys(false)
                    ->variablePrototype()->end()
                ->end()
                ->arrayNode('icons')
                    ->info('Icon overrides keyed by alias, e.g. "modal.close-button: lucide:x".')
                    ->normalizeKeys(false)
                    ->scalarPrototype()->end()
                ->end()
            ->end();
    }

    /**
     * @param array{colors?: array<string, array<int, string>|string>, icons?: array<string, string>} $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $this->configureServices($configurator, $config['colors'] ?? [], $config['icons'] ?? []);
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $this->prependAssetMapper($container);
    }

    private function bootStaticInstances(): void
    {
        $container = $this->container;
        if (!$container instanceof ContainerInterface) {
            throw new RuntimeException('SylamentSupportBundle booted without a container; static instances cannot boot.');
        }

        $registry = $container->get(StaticInstanceRegistry::class);
        if (!$registry instanceof StaticInstanceRegistry) {
            throw new RuntimeException(sprintf('The "%s" service is not a static instance registry.', StaticInstanceRegistry::class));
        }

        $registry->bootStaticInstances();
    }

    /**
     * @param array<string, array<int, string>|string> $colors
     * @param array<string, string> $icons
     */
    private function configureServices(ContainerConfigurator $configurator, array $colors, array $icons): void
    {
        $services = $configurator->services();

        $services
            ->load(__NAMESPACE__ . '\\Components\\', __DIR__ . '/Components/*.php')
            ->autowire()
            ->autoconfigure();

        // `ColorManager` resolves a policy from the service container by class-string, and a
        // private service is not reachable through `Container::get()`, so every glob stays public.
        $services
            ->load(__NAMESPACE__ . '\\Colors\\Components\\', __DIR__ . '/Colors/Components/*.php')
            ->autowire()
            ->public();

        $services
            ->load(__NAMESPACE__ . '\\Colors\\Components\\', __DIR__ . '/Colors/Components/*/*.php')
            ->autowire()
            ->public();

        $services
            ->load(__NAMESPACE__ . '\\Colors\\Components\\', __DIR__ . '/Colors/Components/*/*/*.php')
            ->autowire()
            ->public();

        $services
            ->set('sylament_support.static_instance_registry', StaticInstanceRegistry::class)
            ->args([
                '$services' => tagged_iterator(StaticInstance::class),
            ])
            ->alias(StaticInstanceRegistry::class, 'sylament_support.static_instance_registry')
            ->public();

        $services
            ->set('sylament_support.attribute_extension', AttributeExtension::class)
            ->tag('twig.extension');

        $services
            ->set('sylament_support.color_extension', ColorExtension::class)
            ->autowire()
            ->tag('twig.extension')
            ->alias(ColorExtension::class, 'sylament_support.color_extension');

        $services
            ->set('sylament_support.component_extension', ComponentExtension::class)
            ->tag('twig.extension')
            ->alias(ComponentExtension::class, 'sylament_support.component_extension');

        $services
            ->set('sylament_support.grid_extension', GridExtension::class)
            ->tag('twig.extension');

        $services
            ->set('sylament_support.icon_extension', IconExtension::class)
            ->autowire()
            ->tag('twig.extension');

        $services
            ->set('sylament_support.markup_extension', MarkupExtension::class)
            ->tag('twig.extension');

        $services
            ->set('sylament_support.invoker', Invoker::class)
            ->args([
                '$container' => service('service_container'),
            ])
            ->alias(Invoker::class, 'sylament_support.invoker');

        $services
            ->set('sylament_support.color_manager', ColorManager::class)
            ->args([
                '$container' => service('service_container'),
            ])
            ->call('register', [$colors])
            ->alias(ColorManager::class, 'sylament_support.color_manager');

        $services
            ->set('sylament_support.component_manager', ComponentManager::class)
            ->factory(ComponentManager::resolve(...))
            ->alias(ComponentManager::class, 'sylament_support.component_manager');

        $services
            ->set('sylament_support.icon_manager', IconManager::class)
            ->args([
                '$renderer' => service(IconRendererInterface::class)->nullOnInvalid(),
            ])
            ->call('register', [$icons])
            ->alias(IconManager::class, 'sylament_support.icon_manager');

        $services
            ->set('sylament_support.component_resolver', ComponentResolver::class)
            ->args([
                '$components' => null,
            ])
            ->alias(ComponentResolver::class, 'sylament_support.component_resolver');
    }

    private function prependAssetMapper(ContainerBuilder $container): void
    {
        if (!$this->isAssetMapperAvailable($container)) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__ . '/../assets/dist' => '@sylament/support',
                ],
            ],
        ]);
    }
}
