<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\Concerns;

use Doctrine\Bundle\DoctrineBundle\Middleware\DebugMiddleware;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration as ConnectionConfiguration;
use Doctrine\DBAL\Driver\AbstractSQLiteDriver\Middleware\EnableForeignKeys;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Sylament\Tests\Infrastructure\Doctrine\SqlNormalizer;
use Sylament\Tests\Infrastructure\Doctrine\TestDatabase;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Reads and writes the test's database through the current kernel. Nothing here is
 * memoised: after a request the client has rebooted the kernel, and the manager and
 * the collected statements to read are the new container's.
 */
trait InteractsWithDatabase
{
    protected function entityManager(): EntityManagerInterface
    {
        $entityManager = $this->doctrine()->getManager();

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
     * It shares only the world's event manager: neither the profiling nor the preparation,
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
     * goes to the database, and empties the collected statements, so only the statements
     * under test count.
     */
    protected function seed(object ...$entities): void
    {
        $entityManager = $this->entityManager();

        foreach ($entities as $entity) {
            $entityManager->persist($entity);
        }

        $entityManager->flush();
        $entityManager->clear();

        $this->debugDataHolder()->reset();
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
     * The statements the default connection ran since the last {@see seed()} or services
     * reset, whichever came later, as DoctrineBundle's profiling collected them, with
     * Doctrine's minted names renumbered: each request after a kernel's first starts on
     * reset services or on a rebooted kernel, so assert a count within one request. A
     * transaction reads `"START TRANSACTION"`, `"COMMIT"` and `"ROLLBACK"`, quotes included.
     *
     * @return list<string>
     */
    protected function statementsRun(): array
    {
        $statements = [];
        $collected = $this->debugDataHolder()->getData()[$this->doctrine()->getDefaultConnectionName()] ?? [];

        if (!is_array($collected)) {
            throw new LogicException("DoctrineBundle collected the default connection's statements as something other than a list.");
        }

        foreach ($collected as $query) {
            if (!is_array($query) || !is_string($query['sql'] ?? null)) {
                throw new LogicException('DoctrineBundle collected a statement without its SQL.');
            }

            $statements[] = SqlNormalizer::normalize($query['sql']);
        }

        return $statements;
    }

    private function doctrine(): ManagerRegistry
    {
        $registry = static::getContainer()->get('doctrine');

        if (!$registry instanceof ManagerRegistry) {
            throw new LogicException("The kernel's [doctrine] service is not a manager registry.");
        }

        return $registry;
    }

    /**
     * Throws unless the default connection is profiled, since an unprofiled one
     * collects nothing and every statement count would read zero.
     */
    private function debugDataHolder(): DebugDataHolder
    {
        $middlewares = $this->entityManager()->getConnection()->getConfiguration()->getMiddlewares();

        if (!array_any($middlewares, static fn (Middleware $middleware): bool => $middleware instanceof DebugMiddleware)) {
            throw new LogicException('The default connection is not profiled: turn doctrine.dbal.profiling on in config/packages/doctrine.php.');
        }

        $holder = static::getContainer()->get('doctrine.debug_data_holder');

        if (!$holder instanceof DebugDataHolder) {
            throw new LogicException("The kernel's [doctrine.debug_data_holder] service is not a DebugDataHolder.");
        }

        return $holder;
    }
}
