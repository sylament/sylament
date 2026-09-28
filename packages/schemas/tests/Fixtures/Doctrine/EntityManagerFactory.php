<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Sylament\Testing\Doctrine\RecordingMiddleware;
use Sylament\Testing\Doctrine\TestEntityManagers;

final class EntityManagerFactory
{
    /**
     * The four custom types the fixture mapping names.
     *
     * @var array<string, class-string<Type>>
     */
    private const array TYPES = [
        SkuType::NAME => SkuType::class,
        CipherType::NAME => CipherType::class,
        BinaryUuidType::NAME => BinaryUuidType::class,
        CaselessType::NAME => CaselessType::class,
    ];

    /**
     * The fixture schema on the test database, which is in-memory SQLite unless
     * SYLAMENT_TEST_DATABASE_URL names a server, counting every statement it runs
     * when given a recorder.
     */
    public static function create(?RecordingMiddleware $recorder = null): EntityManager
    {
        return TestEntityManagers::create('schemas', [__DIR__ . '/Entity'], self::TYPES, $recorder);
    }

    /**
     * Always in-memory SQLite, whatever the environment names: the non-native side
     * of a test that compares platforms.
     */
    public static function createForSqlite(?RecordingMiddleware $recorder = null): EntityManager
    {
        return TestEntityManagers::createSqlite('schemas', [__DIR__ . '/Entity'], self::TYPES, $recorder);
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
     * Registers the custom types once per process for createForPostgres(), which builds
     * its manager itself: Doctrine's registry is global, so a second call would throw.
     */
    private static function registerTypes(): void
    {
        foreach (self::TYPES as $name => $class) {
            if (!Type::hasType($name)) {
                Type::addType($name, $class);
            }
        }
    }
}
