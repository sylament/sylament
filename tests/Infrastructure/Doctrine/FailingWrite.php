<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\Doctrine;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use LogicException;

/**
 * Makes a write fail on purpose, on every platform the suite runs on, so a test
 * can watch what a transaction does when one of its later statements fails.
 */
final class FailingWrite
{
    public const string MESSAGE = 'sylament: a failing write the test asked for';

    /**
     * The ($allowed + 1)-th row deleted from the entity's table in this database
     * fails with a driver error, whatever order the rows go in. A counter each
     * row's delete increments inside the caller's transaction does it, so a
     * rollback resets the counter too. On the run's server database, the counter
     * and its trigger are dropped before another test takes the database.
     * Runs its setup on the manager's own connection.
     *
     * @param class-string $entityClass
     */
    public static function afterDeletes(EntityManagerInterface $entityManager, string $entityClass, int $allowed): void
    {
        $platform = $entityManager->getConnection()->getDatabasePlatform();
        $metadata = $entityManager->getClassMetadata($entityClass);

        self::failWritesTo($entityManager, 'DELETE', $entityManager->getConfiguration()->getQuoteStrategy()->getTableName($metadata, $platform), $metadata->getTableName(), $allowed);
    }

    /**
     * {@see afterDeletes()} for the rows updated in the entity's table.
     *
     * @param class-string $entityClass
     */
    public static function afterUpdates(EntityManagerInterface $entityManager, string $entityClass, int $allowed): void
    {
        $platform = $entityManager->getConnection()->getDatabasePlatform();
        $metadata = $entityManager->getClassMetadata($entityClass);

        self::failWritesTo($entityManager, 'UPDATE', $entityManager->getConfiguration()->getQuoteStrategy()->getTableName($metadata, $platform), $metadata->getTableName(), $allowed);
    }

    /**
     * {@see afterDeletes()} for the join table of a many-to-many the class owns, which no
     * entity maps.
     *
     * @param class-string $ownerClass
     */
    public static function afterJoinTableDeletes(EntityManagerInterface $entityManager, string $ownerClass, string $association, int $allowed): void
    {
        $platform = $entityManager->getConnection()->getDatabasePlatform();
        $metadata = $entityManager->getClassMetadata($ownerClass);
        $mapping = $metadata->getAssociationMapping($association);

        if (!$mapping instanceof ManyToManyOwningSideMapping) {
            throw new LogicException(sprintf('[%s::$%s] is not the owning side of a many-to-many, so it has no join table of its own.', $ownerClass, $association));
        }

        self::failWritesTo($entityManager, 'DELETE', $entityManager->getConfiguration()->getQuoteStrategy()->getJoinTableName($mapping, $metadata, $platform), $mapping->joinTable->name, $allowed);
    }

    /**
     * @param 'DELETE'|'UPDATE' $write
     */
    private static function failWritesTo(EntityManagerInterface $entityManager, string $write, string $table, string $tableName, int $allowed): void
    {
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        $name = ($write === 'DELETE' ? 'sy_failing_' : 'sy_failing_update_') . mb_strtolower((string) preg_replace('/\W+/', '_', $tableName));
        $message = self::MESSAGE;
        $row = $write === 'DELETE' ? 'OLD' : 'NEW';

        [$create, $cleanup] = match (true) {
            $platform instanceof SQLitePlatform => [[
                "CREATE TABLE {$name} (writes INTEGER NOT NULL)",
                "INSERT INTO {$name} (writes) VALUES (0)",
                "CREATE TRIGGER {$name} BEFORE {$write} ON {$table} FOR EACH ROW BEGIN UPDATE {$name} SET writes = writes + 1; SELECT RAISE(ABORT, '{$message}') WHERE (SELECT writes FROM {$name}) > {$allowed}; END",
            ], []],
            $platform instanceof AbstractMySQLPlatform => [[
                "CREATE TABLE {$name} (writes INT NOT NULL)",
                "INSERT INTO {$name} (writes) VALUES (0)",
                "CREATE TRIGGER {$name} BEFORE {$write} ON {$table} FOR EACH ROW BEGIN UPDATE {$name} SET writes = writes + 1; IF (SELECT writes FROM {$name}) > {$allowed} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$message}'; END IF; END",
            ], ["DROP TRIGGER IF EXISTS {$name}", "DROP TABLE IF EXISTS {$name}"]],
            $platform instanceof PostgreSQLPlatform => [[
                "CREATE TABLE {$name} (writes INTEGER NOT NULL)",
                "INSERT INTO {$name} (writes) VALUES (0)",
                "CREATE FUNCTION {$name}() RETURNS trigger LANGUAGE plpgsql AS \$\$ BEGIN UPDATE {$name} SET writes = writes + 1; IF (SELECT writes FROM {$name}) > {$allowed} THEN RAISE EXCEPTION '{$message}'; END IF; RETURN {$row}; END \$\$",
                "CREATE TRIGGER {$name} BEFORE {$write} ON {$table} FOR EACH ROW EXECUTE FUNCTION {$name}()",
            ], ["DROP TRIGGER IF EXISTS {$name} ON {$table}", "DROP FUNCTION IF EXISTS {$name}()", "DROP TABLE IF EXISTS {$name}"]],
            default => throw new LogicException(sprintf('FailingWrite knows no trigger for [%s].', $platform::class)),
        };

        foreach ($create as $statement) {
            $connection->executeStatement($statement);
        }

        if ($cleanup !== []) {
            ServerDatabase::instance()->cleanUpBeforeNextWipe($cleanup);
        }
    }
}
