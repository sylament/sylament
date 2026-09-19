<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;

final class EntityManagerFactory
{
    /**
     * An in-memory SQLite entity manager with the fixture schema already built.
     */
    public static function create(): EntityManager
    {
        if (!Type::hasType(SkuType::NAME)) {
            Type::addType(SkuType::NAME, SkuType::class);
        }

        $config = ORMSetup::createAttributeMetadataConfig(paths: [__DIR__ . '/Entity'], isDevMode: true);
        $config->enableNativeLazyObjects(true);

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);

        new SchemaTool($entityManager)->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        return $entityManager;
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
