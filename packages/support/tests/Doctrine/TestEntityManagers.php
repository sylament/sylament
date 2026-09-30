<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Doctrine;

use Closure;
use Doctrine\DBAL\Driver\AbstractSQLiteDriver\Middleware\EnableForeignKeys;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Builds the entity manager a package's test fixtures run on: in-memory SQLite
 * carrying the package's schema, unless SYLAMENT_TEST_DATABASE_URL names a server,
 * where a DatabasePool hands out a database that starts as empty as SQLite's would.
 */
final class TestEntityManagers
{
    /**
     * `$configure` receives the configuration before the manager is built.
     *
     * @param list<string> $entityPaths
     * @param array<string, class-string<Type>> $types
     */
    public static function create(string $package, array $entityPaths, array $types = [], ?RecordingMiddleware $recorder = null, ?Closure $configure = null): EntityManager
    {
        if (TestPlatform::current() !== TestPlatform::Sqlite) {
            return DatabasePool::instance()->entityManager($package, self::configuration($entityPaths, $types, $configure), $recorder);
        }

        return self::createSqlite($package, $entityPaths, $types, $recorder, $configure);
    }

    /**
     * Always in-memory SQLite, whatever the environment names: for a test whose
     * premise compares platforms. It enforces foreign keys, as MySQL and PostgreSQL do.
     * `$configure` receives the configuration before the manager is built.
     *
     * @param list<string> $entityPaths
     * @param array<string, class-string<Type>> $types
     */
    public static function createSqlite(string $package, array $entityPaths, array $types = [], ?RecordingMiddleware $recorder = null, ?Closure $configure = null): EntityManager
    {
        $config = self::configuration($entityPaths, $types, $configure);
        $config->setMiddlewares($recorder instanceof RecordingMiddleware ? [new EnableForeignKeys(), $recorder] : [new EnableForeignKeys()]);

        $entityManager = new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config), $config);
        $build = static function () use ($entityManager): void {
            new SchemaTool($entityManager)->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
        };

        if ($recorder instanceof RecordingMiddleware) {
            $recorder->withoutRecording($build);
        } else {
            $build();
        }

        return $entityManager;
    }

    /**
     * @param list<string> $entityPaths
     * @param array<string, class-string<Type>> $types
     */
    private static function configuration(array $entityPaths, array $types, ?Closure $configure): Configuration
    {
        foreach ($types as $name => $class) {
            if (!Type::hasType($name)) {
                Type::addType($name, $class);
            }
        }

        $config = ORMSetup::createAttributeMetadataConfig(paths: $entityPaths, isDevMode: true);
        $config->enableNativeLazyObjects(true);

        if ($configure instanceof Closure) {
            $configure($config);
        }

        return $config;
    }
}
