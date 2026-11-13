<?php

declare(strict_types=1);

namespace Sylament\Actions\DependencyInjection\Compiler;

use Sylament\Actions\Testing\ExceptionRecorder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * FrameworkBundle defines `test.service_container` only when `framework.test` is on, and an
 * extension's `load()` never sees another extension's services, so test mode is read here.
 */
final class ExceptionRecorderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('test.service_container')) {
            return;
        }

        $container->removeDefinition(ExceptionRecorder::class);
    }
}
