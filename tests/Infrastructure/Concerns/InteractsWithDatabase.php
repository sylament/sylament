<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\Concerns;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration as ConnectionConfiguration;
use Doctrine\DBAL\Driver\AbstractSQLiteDriver\Middleware\EnableForeignKeys;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Sylament\Tests\Infrastructure\Doctrine\RecordingMiddleware;
use Sylament\Tests\Infrastructure\Doctrine\TestDatabase;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Reads and writes the test's database through the current kernel. Nothing here is
 * memoised: after a request the client has rebooted the kernel, and the manager and
 * the recorder to use are the new container's.
 */
trait InteractsWithDatabase
{
    protected function entityManager(): EntityManagerInterface
    {
        $registry = static::getContainer()->get('doctrine');

        if (!$registry instanceof ManagerRegistry) {
            throw new LogicException("The kernel's [doctrine] service is not a manager registry.");
        }

        $entityManager = $registry->getManager();

        if (!$entityManager instanceof EntityManagerInterface) {
            throw new LogicException('The default manager is not an ORM entity manager.');
        }

        return $entityManager;
    }

    /**
     * A manager over the world's mapping pinned to `$platform`, whatever the run names,
     * for a premise that compares platforms. SQLite answers one on an in-memory database
     * of its own, carrying the schema and enforcing foreign keys; MySQL and PostgreSQL
     * answer one over a connection that never opens, which names its platform from its
     * server version, so nothing on it may run a statement.
     *
     * It shares only the world's event manager: neither the recorder nor the preparation,
     * nor the metadata cache, second-level cache or repository services; the reasons are
     * in `docs/internals/test-infrastructure.md`, "Platform-pinned managers".
     */
    protected function platformEntityManager(TestPlatform $platform): EntityManagerInterface
    {
        $world = $this->entityManager();
        $eventManager = $world->getEventManager();

        if (!$eventManager instanceof EventManager) {
            throw new LogicException(sprintf('The default manager dispatches through a [%s], not an event manager.', $eventManager::class));
        }

        $configuration = clone $world->getConfiguration();
        $configuration->setMetadataCache(new ArrayAdapter());
        $configuration->setSecondLevelCacheEnabled(false);
        $configuration->setRepositoryFactory(new DefaultRepositoryFactory());

        if ($platform !== TestPlatform::Sqlite) {
            $connection = DriverManager::getConnection($platform === TestPlatform::MySql
                ? ['driver' => 'pdo_mysql', 'serverVersion' => '8.4.0']
                : ['driver' => 'pdo_pgsql', 'serverVersion' => '18']);

            return new EntityManager($connection, $configuration, $eventManager);
        }

        $connectionConfiguration = new ConnectionConfiguration();
        $connectionConfiguration->setMiddlewares([new EnableForeignKeys()]);

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $connectionConfiguration);
        TestDatabase::buildSchema($connection, $configuration, $eventManager);

        return new EntityManager($connection, $configuration, $eventManager);
    }

    /**
     * Persists and flushes the records, then clears the identity map, so the next read
     * goes to the database, and resets the recorder, so only the statements under test
     * count.
     */
    protected function seed(object ...$entities): void
    {
        $entityManager = $this->entityManager();

        foreach ($entities as $entity) {
            $entityManager->persist($entity);
        }

        $entityManager->flush();
        $entityManager->clear();

        $this->recorder()->reset();
    }

    /**
     * Reads the record's row again by its identifier, through the current manager, in
     * one statement, overwriting whatever instance of it that manager already holds.
     *
     * @template T of object
     *
     * @param T $entity
     *
     * @return T
     */
    protected function reload(object $entity): object
    {
        $entityManager = $this->entityManager();
        $metadata = $entityManager->getClassMetadata($entity::class);
        $identifier = $metadata->getIdentifierValues($entity);

        if ($identifier === []) {
            throw new LogicException(sprintf('The [%s] given carries no identifier to reload it by.', $metadata->getName()));
        }

        $query = $entityManager->createQueryBuilder()->select('record')->from($metadata->getName(), 'record');

        foreach ($identifier as $field => $value) {
            $query->andWhere(sprintf('record.%s = :%s', $field, $field))->setParameter($field, $value);
        }

        $reloaded = $query->getQuery()->setHint(Query::HINT_REFRESH, true)->getOneOrNullResult()
            ?? throw new LogicException(sprintf('No [%s] row has the identifier of the record given.', $metadata->getName()));

        if (!$reloaded instanceof $entity) {
            throw new LogicException(sprintf('Reloading a [%s] answered a [%s].', $entity::class, get_debug_type($reloaded)));
        }

        return $reloaded;
    }

    /**
     * The statements the default connection ran since the last {@see seed()} or kernel
     * reboot, whichever came later, with Doctrine's minted names renumbered: each
     * request after the first reboots the kernel, whose connection has a recorder of
     * its own, so assert a count within one request.
     *
     * @return list<string>
     */
    protected function statementsRun(): array
    {
        return $this->recorder()->normalizedStatements();
    }

    /**
     * The recorder wrapping the default connection: DoctrineBundle builds one per
     * connection, so it is read from the connection rather than fetched by class.
     */
    private function recorder(): RecordingMiddleware
    {
        foreach ($this->entityManager()->getConnection()->getConfiguration()->getMiddlewares() as $middleware) {
            if ($middleware instanceof RecordingMiddleware) {
                return $middleware;
            }
        }

        throw new LogicException('The default connection carries no RecordingMiddleware.');
    }
}
