<?php

declare(strict_types=1);

namespace Sylament\Notifications\DependencyInjection\Compiler;

use Sylament\Notifications\Testing\NotificationRecorder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * FrameworkBundle defines `test.service_container` only when `framework.test` is on, and an
 * extension's `load()` never sees another extension's services, so test mode is read here,
 * before the decoration is resolved.
 */
final class NotificationRecorderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('test.service_container')) {
            return;
        }

        $container->removeDefinition(NotificationRecorder::class);
    }
}
