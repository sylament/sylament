<?php

declare(strict_types=1);

namespace Sylament\Support\Tests;

use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Sylament\Support\DependencyInjection\StaticInstanceRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

abstract class TestCase extends KernelTestCase
{
    use InteractsWithLiveComponents;

    protected ?EntityManagerInterface $entityManager = null {
        get {
            if ($this->entityManager instanceof EntityManagerInterface) {
                return $this->entityManager;
            }

            $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

            return $this->entityManager;
        }
    }

    protected function tearDown(): void
    {
        if (self::$kernel instanceof KernelInterface) {
            self::getContainer()->get(StaticInstanceRegistry::class)->unsetStaticInstances();
        }

        parent::tearDown();
    }

    protected function setUpDatabase(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function tearDownDatabase(): void
    {
        if ($this->entityManager instanceof EntityManagerInterface) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }

    /**
     * @param class-string<Fixture> $fixture
     */
    protected function executeFixture(string $fixture): void
    {
        $loader = new Loader();

        $loader->addFixture(new $fixture);

        new ORMExecutor($this->entityManager, new ORMPurger())
            ->execute($loader->getFixtures());
    }
}
