<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Doctrine;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

/**
 * Makes a write fail on purpose, on every platform the suites run on, so a test
 * can watch what a transaction does when one of its later statements fails.
 */
final class FailingWrite
{
    public const string MESSAGE = 'sylament: a failing write the test asked for';

    /**
     * The ($allowed + 1)-th row deleted from the entity's table in this database
     * fails with a driver error, whatever order the rows go in. A counter each
     * row's delete increments inside the caller's transaction does it, so a
     * rollback resets the counter too. On a pooled server database, the counter
     * and its trigger are dropped before another test takes the database.
     * Runs its setup, and on a server one SELECT naming the database, on the
     * manager's own connection; a caller holding a recorder resets it afterwards.
     *
     * @param class-string $entityClass
     */
    public static function afterDeletes(EntityManagerInterface $entityManager, string $entityClass, int $allowed): void
    {
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        $metadata = $entityManager->getClassMetadata($entityClass);
        $table = $entityManager->getConfiguration()->getQuoteStrategy()->getTableName($metadata, $platform);
        $name = 'sy_failing_' . mb_strtolower((string) preg_replace('/\W+/', '_', $metadata->getTableName()));
        $message = self::MESSAGE;

        [$create, $cleanup] = match (true) {
            $platform instanceof SQLitePlatform => [[
                "CREATE TABLE {$name} (deletes INTEGER NOT NULL)",
                "INSERT INTO {$name} (deletes) VALUES (0)",
                "CREATE TRIGGER {$name} BEFORE DELETE ON {$table} FOR EACH ROW BEGIN UPDATE {$name} SET deletes = deletes + 1; SELECT RAISE(ABORT, '{$message}') WHERE (SELECT deletes FROM {$name}) > {$allowed}; END",
            ], []],
            $platform instanceof AbstractMySQLPlatform => [[
                "CREATE TABLE {$name} (deletes INT NOT NULL)",
                "INSERT INTO {$name} (deletes) VALUES (0)",
                "CREATE TRIGGER {$name} BEFORE DELETE ON {$table} FOR EACH ROW BEGIN UPDATE {$name} SET deletes = deletes + 1; IF (SELECT deletes FROM {$name}) > {$allowed} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$message}'; END IF; END",
            ], ["DROP TRIGGER IF EXISTS {$name}", "DROP TABLE IF EXISTS {$name}"]],
            $platform instanceof PostgreSQLPlatform => [[
                "CREATE TABLE {$name} (deletes INTEGER NOT NULL)",
                "INSERT INTO {$name} (deletes) VALUES (0)",
                "CREATE FUNCTION {$name}() RETURNS trigger LANGUAGE plpgsql AS \$\$ BEGIN UPDATE {$name} SET deletes = deletes + 1; IF (SELECT deletes FROM {$name}) > {$allowed} THEN RAISE EXCEPTION '{$message}'; END IF; RETURN OLD; END \$\$",
                "CREATE TRIGGER {$name} BEFORE DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION {$name}()",
            ], ["DROP TRIGGER IF EXISTS {$name} ON {$table}", "DROP FUNCTION IF EXISTS {$name}()", "DROP TABLE IF EXISTS {$name}"]],
            default => throw new LogicException(sprintf('FailingWrite knows no trigger for [%s].', $platform::class)),
        };

        foreach ($create as $statement) {
            $connection->executeStatement($statement);
        }

        if ($cleanup !== []) {
            DatabasePool::instance()->cleanUpBeforeNextWipe($connection->getDatabase() ?? throw new LogicException('A pooled connection names no database.'), $cleanup);
        }
    }
}
