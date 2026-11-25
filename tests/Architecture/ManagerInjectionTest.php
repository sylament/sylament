<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Replication\RecordReplicator;
use Sylament\Tests\Fixtures\Kernel;
use Sylament\Tests\Infrastructure\DependencyInjection\ManagerInjectionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Architectural convention test keeping the container's default entity manager and connection out of every shipped
 * service, since an application may map its classes on several managers; the reasoning is in docs/internals/php.md.
 */

it('hands no shipped service an entity manager or a connection from the container', function (): void {
    expect(new Kernel('test', true)->getCompiledContainer()->getParameter(ManagerInjectionPass::PARAMETER))->toBe([]);
});

it('reports a shipped class the container hands an entity manager', function (): void {
    $container = new ContainerBuilder();
    $container->register(EntityManagerInterface::class, EntityManager::class);
    $container->register(RecordReplicator::class, RecordReplicator::class)->setArguments([new Reference(EntityManagerInterface::class)]);

    expect(ManagerInjectionPass::injectionsIn($container))->toBe([RecordReplicator::class . '::__construct()']);
});
