<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\Doctrine;

use Closure;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use RuntimeException;
use Sylament\Tests\Infrastructure\WorkerDirectory;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The root suite's one database per test, shared by every manager the kernel's
 * `doctrine` registry holds, in-process or in any request. Its state is the
 * process's, not a container's: the client reboots the kernel between requests,
 * and a database prepared per container would lose the test's seed.
 *
 * Each test starts stale ({@see testStarted()}); the first connection the test opens
 * prepares it ({@see prepare()}), later ones only connect, and a test that never
 * connects pays nothing.
 */
final class TestDatabase
{
    /**
     * The environment variable the kernel's `default` connection reads its URL from
     * at runtime, so a compiled container never freezes a worker's or a server's URL.
     */
    public const string URL_VARIABLE = 'SYLAMENT_TEST_CONNECTION_URL';

    private const array SQLITE_SIDECARS = ['-journal', '-wal', '-shm'];

    private static bool $stale = true;

    private static bool $preparing = false;

    private static bool $templateBuilt = false;

    private static ?ArrayAdapter $metadataCache = null;

    /**
     * The connections the kernel opened during the current test.
     *
     * @var array<int, Connection>
     */
    private static array $handedOut = [];

    /**
     * SQLite: the worker's file; a server: SYLAMENT_TEST_DATABASE_URL naming the
     * pool's database for this process instead of the one it names.
     */
    public static function url(): string
    {
        if (TestPlatform::current() === TestPlatform::Sqlite) {
            return 'sqlite:///' . implode('/', array_map(rawurlencode(...), explode('/', self::sqliteFile('test'))));
        }

        $url = TestPlatform::url() ?? throw new LogicException(sprintf('%s names no server.', TestPlatform::URL_VARIABLE));

        $renamed = preg_replace('#^([^:/?\#]+://[^/?\#]*)/[^?\#]*#', '$1/' . DatabasePool::instance()->worldName(), $url, count: $replaced)
            ?? throw new RuntimeException(preg_last_error_msg());

        if ($replaced !== 1) {
            throw new LogicException(sprintf('%s must name a database, as in …/sylament_test.', TestPlatform::URL_VARIABLE));
        }

        return $renamed;
    }

    /**
     * Called as each test starts: closes the connections the previous test was handed,
     * which rolls back anything it left open, and marks the database stale.
     */
    public static function testStarted(): void
    {
        foreach (self::$handedOut as $connection) {
            $connection->close();
        }

        self::$handedOut = [];
        self::$stale = true;
    }

    /**
     * Called before the kernel's connection connects. Once per test, it empties the
     * database: SQLite copies a template over the worker's file, a server has the
     * pool wipe the worker's database. The schema is built once per process, at the
     * first preparation, from every manager `$doctrine` holds, on a connection of
     * its own, so neither the recorder nor the connecting connection sees it.
     *
     * @param Closure(): ManagerRegistry $doctrine
     */
    public static function prepare(Closure $doctrine): void
    {
        if (self::$preparing) {
            throw new LogicException("Preparing the test database opened the kernel's connection again.");
        }

        $registry = $doctrine();

        foreach ($registry->getConnections() as $connection) {
            if ($connection instanceof Connection) {
                self::$handedOut[spl_object_id($connection)] = $connection;
            }
        }

        if (!self::$stale) {
            return;
        }

        self::$preparing = true;

        try {
            if (TestPlatform::current() === TestPlatform::Sqlite) {
                self::prepareSqlite($registry);
            } else {
                DatabasePool::instance()->prepareWorld(static function (Connection $connection) use ($registry): void {
                    self::createSchema($connection, $registry);
                });
            }

            self::$stale = false;
        } finally {
            self::$preparing = false;
        }
    }

    /**
     * The metadata cache every manager of the process shares: fresh every run, warm
     * within it.
     */
    public static function metadataCache(): ArrayAdapter
    {
        return self::$metadataCache ??= new ArrayAdapter();
    }

    private static function prepareSqlite(ManagerRegistry $registry): void
    {
        $filesystem = new Filesystem();
        $template = self::sqliteFile('template');
        $database = self::sqliteFile('test');

        if (!self::$templateBuilt) {
            $filesystem->mkdir(dirname($template));
            $filesystem->remove(self::withSidecars($template));

            $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'path' => $template]);
            self::createSchema($connection, $registry);
            $connection->close();

            self::$templateBuilt = true;
        }

        // A sidecar left beside the old file would replay onto the fresh copy.
        $filesystem->remove(self::withSidecars($database));

        if (!copy($template, $database)) {
            throw new RuntimeException(sprintf('Could not copy the template database to %s.', $database));
        }
    }

    /**
     * Builds the schema a manager over `$configuration` and `$eventManager` maps, on
     * `$connection`, through a manager of its own, so the caller's managers never see it.
     */
    public static function buildSchema(Connection $connection, Configuration $configuration, EventManager $eventManager): void
    {
        $schemaManager = new EntityManager($connection, $configuration, $eventManager);

        foreach (new SchemaTool($schemaManager)->getCreateSchemaSql($schemaManager->getMetadataFactory()->getAllMetadata()) as $sql) {
            $connection->executeStatement($sql);
        }
    }

    private static function createSchema(Connection $connection, ManagerRegistry $registry): void
    {
        foreach ($registry->getManagers() as $name => $manager) {
            if (!$manager instanceof EntityManagerInterface) {
                throw new LogicException(sprintf('The manager [%s] is not an ORM entity manager.', $name));
            }

            $eventManager = $manager->getEventManager();

            if (!$eventManager instanceof EventManager) {
                throw new LogicException(sprintf('The manager [%s] dispatches through a [%s], not an event manager.', $name, $eventManager::class));
            }

            self::buildSchema($connection, $manager->getConfiguration(), $eventManager);
        }
    }

    private static function sqliteFile(string $name): string
    {
        return WorkerDirectory::path() . '/database/' . $name . '.sqlite';
    }

    /**
     * @return list<string>
     */
    private static function withSidecars(string $file): array
    {
        return [$file, ...array_map(static fn (string $suffix): string => $file . $suffix, self::SQLITE_SIDECARS)];
    }
}
