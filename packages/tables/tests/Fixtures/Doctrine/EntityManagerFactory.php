<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine;

use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Sylament\Support\Tests\Doctrine\TestEntityManagers;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class EntityManagerFactory
{
    /**
     * The fixture schema on the test database, which is in-memory SQLite unless
     * SYLAMENT_TEST_DATABASE_URL names a server, counting every statement it runs
     * when given a recorder. The second-level cache is on, in memory and per
     * manager; it caches only an entity marked `#[ORM\Cache]`.
     */
    public static function create(?RecordingMiddleware $recorder = null): EntityManager
    {
        return TestEntityManagers::create('tables', [__DIR__ . '/Entity'], [
            HandleType::NAME => HandleType::class,
            LockerCodeType::NAME => LockerCodeType::class,
            HexCodeType::NAME => HexCodeType::class,
            MarkedCodeType::NAME => MarkedCodeType::class,
        ], $recorder, static function (Configuration $config): void {
            $cache = new CacheConfiguration();
            $cache->setCacheFactory(new DefaultCacheFactory(new RegionsConfiguration(), new ArrayAdapter()));

            $config->setSecondLevelCacheEnabled();
            $config->setSecondLevelCacheConfiguration($cache);
        });
    }

    /**
     * Clears the identity map after flushing, so a subsequent read goes to the
     * database rather than answering from memory.
     */
    public static function seed(EntityManager $entityManager, object ...$entities): void
    {
        foreach ($entities as $entity) {
            $entityManager->persist($entity);
        }

        $entityManager->flush();
        $entityManager->clear();
    }
}
