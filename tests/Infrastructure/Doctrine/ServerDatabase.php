<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\Doctrine;

use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use LogicException;
use Sylament\Tests\Infrastructure\WorkerDirectory;

/**
 * The run's database on a MySQL or PostgreSQL server, `<base>_<run>`: the database
 * the URL names, then the run's name. {@see prepare()} creates it at its first use
 * and wipes it, identity counters included, before every later test, on a
 * connection of its own, so nothing it runs reaches a test's statement count.
 *
 * @internal
 *
 * @phpstan-import-type Params from DriverManager
 */
final class ServerDatabase
{
    private static ?self $instance = null;

    /**
     * Its own connection to the database once it is built. Every wipe runs on it.
     */
    private ?Connection $housekeeping = null;

    /**
     * Quoted table names.
     *
     * @var list<string>
     */
    private array $tables = [];

    /**
     * MySQL's auto-increment table names.
     *
     * @var list<string>
     */
    private array $counters = [];

    /**
     * @var list<string>
     */
    private array $cleanups = [];

    private ?Connection $server = null;

    /**
     * @param Params $params
     */
    private function __construct(
        private readonly TestPlatform $platform,
        private readonly array $params,
        private readonly string $name,
    ) {
    }

    public static function instance(): self
    {
        return self::$instance ??= self::fromUrl(TestPlatform::url() ?? throw new LogicException(sprintf('%s is not set, so there is no server to run on.', TestPlatform::URL_VARIABLE)));
    }

    public static function fromUrl(string $url): self
    {
        $params = new DsnParser(['mysql' => 'pdo_mysql', 'postgresql' => 'pdo_pgsql', 'pgsql' => 'pdo_pgsql'])->parse($url);
        $base = $params['dbname'] ?? '';

        if ($base === '') {
            throw new LogicException(sprintf('%s must name a database the run derives its own from, as in …/sylament_test.', TestPlatform::URL_VARIABLE));
        }

        unset($params['dbname']);

        return new self(isset($params['driver']) && $params['driver'] === 'pdo_mysql' ? TestPlatform::MySql : TestPlatform::PostgreSql, $params, $base . '_' . WorkerDirectory::run());
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * Readies the database for the next test. The first call creates it and hands
     * `$build` its own connection to it, and creates it again after a build that
     * threw; every later call runs the queued clean-ups, then wipes it, identity
     * counters included.
     *
     * @param Closure(Connection): void $build
     */
    public function prepare(Closure $build): void
    {
        if ($this->housekeeping instanceof Connection) {
            $this->wipe($this->housekeeping);

            return;
        }

        $connection = $this->create();
        $build($connection);

        $this->housekeeping = $connection;
        $this->tables = $this->readTables($connection);
        $this->counters = $this->readCounters($connection);
    }

    /**
     * Statements run just before the next wipe: how a test's own trigger or helper
     * table is gone before the next test starts.
     *
     * @param list<string> $statements
     */
    public function cleanUpBeforeNextWipe(array $statements): void
    {
        $this->cleanups = [...$this->cleanups, ...$statements];
    }

    /**
     * Drops and creates the database, and opens its own connection to it.
     */
    private function create(): Connection
    {
        $server = $this->server();
        $name = $server->getDatabasePlatform()->quoteSingleIdentifier($this->name);

        $server->executeStatement($this->platform === TestPlatform::PostgreSql
            ? "DROP DATABASE IF EXISTS {$name} WITH (FORCE)"
            : "DROP DATABASE IF EXISTS {$name}");
        $server->executeStatement("CREATE DATABASE {$name}");

        return $this->connect();
    }

    private function wipe(Connection $connection): void
    {
        foreach ($this->cleanups as $statement) {
            $connection->executeStatement($statement);
        }

        $this->cleanups = [];

        if ($this->tables === []) {
            return;
        }

        if ($this->platform === TestPlatform::PostgreSql) {
            $connection->executeStatement('TRUNCATE ' . implode(', ', $this->tables) . ' RESTART IDENTITY CASCADE');

            return;
        }

        $nonEmpty = $connection->fetchFirstColumn(implode(' UNION ALL ', array_map(
            static fn (string $table): string => sprintf('SELECT %s FROM DUAL WHERE EXISTS (SELECT 1 FROM %s)', $connection->quote($table), $table),
            $this->tables,
        )));

        foreach ($nonEmpty as $table) {
            $connection->executeStatement('DELETE FROM ' . self::text($table));
        }

        if ($this->counters === []) {
            return;
        }

        $moved = $connection->fetchFirstColumn(sprintf(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND auto_increment > 1 AND table_name IN (%s)',
            implode(', ', array_map($connection->quote(...), $this->counters)),
        ));

        foreach ($moved as $table) {
            $connection->executeStatement(sprintf('ALTER TABLE %s AUTO_INCREMENT = 1', $connection->getDatabasePlatform()->quoteSingleIdentifier(self::text($table))));
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

        return array_map(static fn (mixed $name): string => $platform->quoteSingleIdentifier(self::text($name)), $names);
    }

    /**
     * @return list<string>
     */
    private function readCounters(Connection $connection): array
    {
        if ($this->platform === TestPlatform::PostgreSql) {
            return [];
        }

        return array_map(self::text(...), $connection->fetchFirstColumn(
            "SELECT table_name FROM information_schema.columns WHERE table_schema = DATABASE() AND extra LIKE '%auto_increment%'",
        ));
    }

    private function connect(): Connection
    {
        $connection = DriverManager::getConnection([...$this->params, 'dbname' => $this->name]);
        $session = $this->platform === TestPlatform::PostgreSql
            ? ["SET lock_timeout = '10s'"]
            : ['SET SESSION foreign_key_checks = 0', 'SET SESSION information_schema_stats_expiry = 0', 'SET SESSION lock_wait_timeout = 10', 'SET SESSION innodb_lock_wait_timeout = 10'];

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

    private static function text(mixed $value): string
    {
        if (!is_string($value)) {
            throw new LogicException('A schema query answered something other than a name.');
        }

        return $value;
    }
}
