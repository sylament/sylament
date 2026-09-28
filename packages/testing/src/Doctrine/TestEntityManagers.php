<?php

declare(strict_types=1);

namespace Sylament\Testing\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use LogicException;

/**
 * Builds the entity manager a package's test fixtures run on: in-memory SQLite
 * carrying the package's schema, unless SYLAMENT_TEST_DATABASE_URL names a server.
 */
final class TestEntityManagers
{
    /**
     * @param list<string> $entityPaths
     * @param array<string, class-string<Type>> $types
     */
    public static function create(string $package, array $entityPaths, array $types = [], ?RecordingMiddleware $recorder = null): EntityManager
    {
        $platform = TestPlatform::current();

        if ($platform !== TestPlatform::Sqlite) {
            throw new LogicException(sprintf('%s names a %s server, and no database pool is wired to run on it.', TestPlatform::URL_VARIABLE, $platform->name));
        }

        return self::createSqlite($package, $entityPaths, $types, $recorder);
    }

    /**
     * Always in-memory SQLite, whatever the environment names: for a test whose
     * premise compares platforms.
     *
     * @param list<string> $entityPaths
     * @param array<string, class-string<Type>> $types
     */
    public static function createSqlite(string $package, array $entityPaths, array $types = [], ?RecordingMiddleware $recorder = null): EntityManager
    {
        $config = self::configuration($entityPaths, $types);
        $config->setMiddlewares($recorder instanceof RecordingMiddleware ? [$recorder] : []);

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
    private static function configuration(array $entityPaths, array $types): Configuration
    {
        foreach ($types as $name => $class) {
            if (!Type::hasType($name)) {
                Type::addType($name, $class);
            }
        }

        $config = ORMSetup::createAttributeMetadataConfig(paths: $entityPaths, isDevMode: true);
        $config->enableNativeLazyObjects(true);

        return $config;
    }
}
