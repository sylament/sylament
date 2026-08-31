<?php

declare(strict_types=1);

namespace Sylament\Panels\DependencyInjection\Compiler;

use LogicException;
use ReflectionClass;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

final class RelationManagerComponentPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $names = [];

        foreach (array_keys($container->findTaggedServiceIds('sylament.panels.relation_manager')) as $id) {
            $definition = $container->findDefinition($id);

            if ($definition->isAbstract()) {
                continue;
            }

            $class = $definition->getClass() ?? $id;

            if (! is_subclass_of($class, RelationManager::class)) {
                continue;
            }

            if ([] === new ReflectionClass($class)->getAttributes(AsLiveComponent::class)) {
                throw new LogicException(sprintf(
                    'Relation manager "%s" must carry a bare #[AsLiveComponent] attribute. UX Live Components recognises a live component by reflecting for it; without it the manager is silently re-created on every re-render of the page embedding it, losing its sort, page and selection.',
                    $class,
                ));
            }

            $name = $class::getComponentName();

            if (isset($names[$name])) {
                throw new LogicException(sprintf('Two relation managers derive the live component name "%s": %s and %s.', $name, $names[$name], $id));
            }

            $names[$name] = $id;

            $definition
                ->clearTag('twig.component')
                ->addTag('twig.component', [
                    'key' => $name,
                    'live' => true,
                    'route' => 'ux_live_component',
                    'method' => 'post',
                    'url_reference_type' => UrlGeneratorInterface::ABSOLUTE_PATH,
                    'expose_public_props' => true,
                    'attributes_var' => 'attributes',
                    'template_from_method' => 'getTemplate',
                ])
                ->addTag('controller.service_arguments');
        }
    }
}
