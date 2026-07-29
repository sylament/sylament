<?php

declare(strict_types=1);

namespace Sylament\Support;

use RuntimeException;
use Sylament\Support\Components\Component;
use Sylament\Support\Configuration\ComponentManager;
use Sylament\Support\DependencyInjection\Compiler\ComponentResolverPass;
use Sylament\Support\DependencyInjection\Compiler\StaticInstancePass;
use Sylament\Support\DependencyInjection\ComponentResolver;
use Sylament\Support\DependencyInjection\Contracts\StaticInstance;
use Sylament\Support\DependencyInjection\StaticInstanceRegistry;
use Sylament\Support\Icons\IconManager;
use Sylament\Support\Evaluation\Invoker;
use Sylament\Support\Twig\Extension\AttributeExtension;
use Sylament\Support\Twig\Extension\ComponentExtension;
use Sylament\Support\Twig\Extension\GridExtension;
use Sylament\Support\Twig\Extension\IconExtension;
use Sylament\Support\Twig\Extension\MarkupExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\UX\Icons\IconRendererInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

class SylamentSupportBundle extends SylamentBundle
{
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

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $this->configureServices($configurator);
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

    private function configureServices(ContainerConfigurator $configurator): void
    {
        $services = $configurator->services();

        $services
            ->load(__NAMESPACE__ . '\\Components\\', __DIR__ . '/Components/*.php')
            ->autowire()
            ->autoconfigure();

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
            ->set('sylament_support.component_extension', ComponentExtension::class)
            ->tag('twig.extension')
            ->alias(ComponentExtension::class, 'sylament_support.component_extension');

        $services
            ->set('sylament_support.grid_extension', GridExtension::class)
            ->tag('twig.extension');

        $services
            ->set('sylament_support.icon_extension', IconExtension::class)
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
            ->set('sylament_support.component_manager', ComponentManager::class)
            ->factory(ComponentManager::resolve(...))
            ->alias(ComponentManager::class, 'sylament_support.component_manager');

        $services
            ->set('sylament_support.icon_manager', IconManager::class)
            ->args([
                '$renderer' => service(IconRendererInterface::class)->nullOnInvalid(),
            ])
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
