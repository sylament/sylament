<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\PHPUnit;

use LogicException;
use Sylament\Support\DependencyInjection\StaticInstanceRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class LegacyTestCase extends KernelTestCase
{
    protected function tearDown(): void
    {
        if (self::$kernel instanceof KernelInterface) {
            $registry = self::getContainer()->get(StaticInstanceRegistry::class);

            if (!$registry instanceof StaticInstanceRegistry) {
                throw new LogicException(sprintf('The container has no [%s].', StaticInstanceRegistry::class));
            }

            $registry->unsetStaticInstances();
        }

        parent::tearDown();
    }
}
