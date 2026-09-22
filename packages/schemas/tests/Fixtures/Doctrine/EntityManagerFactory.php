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
     * An in-memory SQLite entity manager with the fixture schema already built,
     * counting every statement it runs when given a recorder.
     */
    public static function create(?RecordingMiddleware $recorder = null): EntityManager
    {
        self::registerTypes();

        $config = ORMSetup::createAttributeMetadataConfig(paths: [__DIR__ . '/Entity'], isDevMode: true);
        $config->enableNativeLazyObjects(true);

        if ($recorder instanceof RecordingMiddleware) {
            $config->setMiddlewares([$recorder]);
        }

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);

        new SchemaTool($entityManager)->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        return $entityManager;
    }

    /**
     * The same fixture mapping over a PostgreSQL connection that never opens:
     * a connection told its server version names its platform from that
     * parameter alone, which is all a platform-gated shape check reads. Nothing
     * here may run a statement.
     */
    public static function createForPostgres(): EntityManager
    {
        self::registerTypes();

        $config = ORMSetup::createAttributeMetadataConfig(paths: [__DIR__ . '/Entity'], isDevMode: true);
        $config->enableNativeLazyObjects(true);

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_pgsql',
            'host' => '127.0.0.1',
            'dbname' => 'sylament_fixture',
            'serverVersion' => '16.0',
        ], $config);

        return new EntityManager($connection, $config);
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

    /**
     * The three custom types the fixture mapping names, registered once per
     * process: Doctrine's registry is global, so a second call would throw.
     */
    private static function registerTypes(): void
    {
        if (!Type::hasType(SkuType::NAME)) {
            Type::addType(SkuType::NAME, SkuType::class);
        }

        if (!Type::hasType(CipherType::NAME)) {
            Type::addType(CipherType::NAME, CipherType::class);
        }

        if (!Type::hasType(BinaryUuidType::NAME)) {
            Type::addType(BinaryUuidType::NAME, BinaryUuidType::class);
        }
    }
}
