<?php

declare(strict_types=1);

namespace Sylament\Support\Tests;

use Sylament\Support\DependencyInjection\StaticInstanceRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class TestCase extends KernelTestCase
{
    protected function tearDown(): void
    {
        if (self::$kernel instanceof KernelInterface) {
            self::getContainer()->get(StaticInstanceRegistry::class)->unsetStaticInstances();
        }

        parent::tearDown();
    }
}
