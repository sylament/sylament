<?php

declare(strict_types=1);

namespace Sylament\Testing\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use LogicException;

/**
 * Gives every test the databases in-memory SQLite would. The k-th entity manager a
 * test creates gets a server database of its own, built once per process and
 * wiped, identity counters included, whenever a later test takes it. Nothing the
 * pool runs reaches a test's recorder.
 *
 * @internal
 *
 * @phpstan-import-type Params from DriverManager
 */
final class DatabasePool
{
    private static ?self $instance = null;

    private int $slot = 0;

    /**
     * @var list<Connection>
     */
    private array $handedOut = [];

    /**
     * The pool's own connection to each database it built. Every wipe runs on it.
     *
     * @var array<string, Connection>
     */
    private array $housekeeping = [];

    /**
     * Quoted table names, by package.
     *
     * @var array<string, list<string>>
     */
    private array $tables = [];

    /**
     * MySQL's auto-increment table names, by package.
     *
     * @var array<string, list<string>>
     */
    private array $counters = [];

    /**
     * @var array<string, list<string>>
     */
    private array $cleanups = [];

    private ?Connection $server = null;

    /**
     * @param Params $params
     */
    private function __construct(
        private readonly TestPlatform $platform,
        private readonly array $params,
        private readonly string $base,
    ) {
    }

    public static function instance(): self
    {
        return self::$instance ??= self::fromUrl(TestPlatform::url() ?? throw new LogicException(sprintf('%s is not set, so there is no server to pool.', TestPlatform::URL_VARIABLE)));
    }

    public static function fromUrl(string $url): self
    {
        $params = new DsnParser(['mysql' => 'pdo_mysql', 'postgresql' => 'pdo_pgsql', 'pgsql' => 'pdo_pgsql'])->parse($url);
        $base = $params['dbname'] ?? '';

        if ($base === '') {
            throw new LogicException(sprintf('%s must name a database the pool derives its own from, as in …/sylament_test.', TestPlatform::URL_VARIABLE));
        }

        unset($params['dbname']);

        return new self(isset($params['driver']) && $params['driver'] === 'pdo_mysql' ? TestPlatform::MySql : TestPlatform::PostgreSql, $params, $base);
    }

    /**
     * Called as each test starts. The next manager is the test's first again, and
     * the connections the previous test was handed are closed, which rolls back
     * anything it left open.
     */
    public function testStarted(): void
    {
        foreach ($this->handedOut as $connection) {
            $connection->close();
        }

        $this->handedOut = [];
        $this->slot = 0;
    }

    public function entityManager(string $package, Configuration $config, ?RecordingMiddleware $recorder): EntityManager
    {
        $database = sprintf('%s_%s_%s_%d', $this->base, $package, getenv('TEST_TOKEN') ?: '0', ++$this->slot);

        if (isset($this->housekeeping[$database])) {
            $this->wipe($package, $database);
        } else {
            $this->build($package, $database, $config);
        }

        $config->setMiddlewares($recorder instanceof RecordingMiddleware ? [$recorder] : []);

        $connection = DriverManager::getConnection([...$this->params, 'dbname' => $database], $config);
        $this->handedOut[] = $connection;

        return new EntityManager($connection, $config);
    }

    /**
     * Statements run on the database just before its next wipe: how a test's own
     * trigger or helper table is gone before another test takes the database.
     *
     * @param list<string> $statements
     */
    public function cleanUpBeforeNextWipe(string $database, array $statements): void
    {
        $this->cleanups[$database] = [...$this->cleanups[$database] ?? [], ...$statements];
    }

    private function build(string $package, string $database, Configuration $config): void
    {
        $server = $this->server();
        $name = $server->getDatabasePlatform()->quoteSingleIdentifier($database);

        $server->executeStatement($this->platform === TestPlatform::PostgreSql
            ? "DROP DATABASE IF EXISTS {$name} WITH (FORCE)"
            : "DROP DATABASE IF EXISTS {$name}");
        $server->executeStatement("CREATE DATABASE {$name}");

        $connection = $this->housekeeping[$database] = $this->connect($database);
        $schemaManager = new EntityManager($connection, $config);

        foreach (new SchemaTool($schemaManager)->getCreateSchemaSql($schemaManager->getMetadataFactory()->getAllMetadata()) as $sql) {
            $connection->executeStatement($sql);
        }

        $this->tables[$package] ??= $this->readTables($connection);
        $this->counters[$package] ??= $this->readCounters($connection);
    }

    private function wipe(string $package, string $database): void
    {
        $connection = $this->housekeeping[$database];

        foreach ($this->cleanups[$database] ?? [] as $statement) {
            $connection->executeStatement($statement);
        }

        unset($this->cleanups[$database]);

        $tables = $this->tables[$package] ?? [];

        if ($tables === []) {
            return;
        }

        if ($this->platform === TestPlatform::PostgreSql) {
            $connection->executeStatement('TRUNCATE ' . implode(', ', $tables) . ' RESTART IDENTITY CASCADE');

            return;
        }

        $nonEmpty = $connection->fetchFirstColumn(implode(' UNION ALL ', array_map(
            static fn (string $table): string => sprintf('SELECT %s FROM DUAL WHERE EXISTS (SELECT 1 FROM %s)', $connection->quote($table), $table),
            $tables,
        )));

        foreach ($nonEmpty as $table) {
            $connection->executeStatement('DELETE FROM ' . self::name($table));
        }

        $counters = $this->counters[$package] ?? [];

        if ($counters === []) {
            return;
        }

        $moved = $connection->fetchFirstColumn(sprintf(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND auto_increment > 1 AND table_name IN (%s)',
            implode(', ', array_map($connection->quote(...), $counters)),
        ));

        foreach ($moved as $table) {
            $connection->executeStatement(sprintf('ALTER TABLE %s AUTO_INCREMENT = 1', $connection->getDatabasePlatform()->quoteSingleIdentifier(self::name($table))));
        }
    }

    /**
     * @return list<string>
     */
    private function readTables(Connection $connection): array
    {
        $platform = $connection->getDatabasePlatform();
        $names = $connection->fetchFirstColumn($this->platform === TestPlatform::PostgreSql
            ? "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename"
            : "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' ORDER BY table_name");

        return array_map(static fn (mixed $name): string => $platform->quoteSingleIdentifier(self::name($name)), $names);
    }

    /**
     * @return list<string>
     */
    private function readCounters(Connection $connection): array
    {
        if ($this->platform === TestPlatform::PostgreSql) {
            return [];
        }

        return array_map(self::name(...), $connection->fetchFirstColumn(
            "SELECT table_name FROM information_schema.columns WHERE table_schema = DATABASE() AND extra LIKE '%auto_increment%'",
        ));
    }

    private function connect(string $database): Connection
    {
        $connection = DriverManager::getConnection([...$this->params, 'dbname' => $database]);
        $session = $this->platform === TestPlatform::PostgreSql
            ? ["SET lock_timeout = '10s'"]
            : ['SET SESSION foreign_key_checks = 0', 'SET SESSION information_schema_stats_expiry = 0', 'SET SESSION lock_wait_timeout = 10'];

        foreach ($session as $statement) {
            $connection->executeStatement($statement);
        }

        return $connection;
    }

    private function server(): Connection
    {
        return $this->server ??= DriverManager::getConnection($this->platform === TestPlatform::PostgreSql
            ? [...$this->params, 'dbname' => 'postgres']
            : $this->params);
    }

    private static function name(mixed $value): string
    {
        if (!is_string($value)) {
            throw new LogicException('A schema query answered something other than a name.');
        }

        return $value;
    }
}
