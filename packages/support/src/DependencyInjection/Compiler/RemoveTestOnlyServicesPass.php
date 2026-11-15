<?php

declare(strict_types=1);

namespace Sylament\Support\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * FrameworkBundle defines `test.service_container` only when `framework.test` is on, and an
 * extension's `load()` never sees another extension's services, so test mode is read here,
 * before a removed service's decoration or listener tag is resolved.
 */
final class RemoveTestOnlyServicesPass implements CompilerPassInterface
{
    public const string TAG = 'sylament.test_only';

    public function process(ContainerBuilder $container): void
    {
        // Read in test mode too: an unread tag is logged by `UnusedTagsPass` on every compile.
        $testOnlyServiceIds = array_keys($container->findTaggedServiceIds(self::TAG));

        if ($container->hasDefinition('test.service_container')) {
            return;
        }

        foreach ($testOnlyServiceIds as $id) {
            $container->removeDefinition($id);
        }
    }
}
