<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Symfony\Contracts\Service\Attribute\Required;

trait CanUseDatabaseTransactions
{
    protected bool|Closure $hasDatabaseTransactions = false;

    protected ?ManagerRegistry $managerRegistry = null;

    #[Required]
    public function setManagerRegistry(?ManagerRegistry $managerRegistry = null): void
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function databaseTransaction(bool|Closure $condition = true): static
    {
        $this->hasDatabaseTransactions = $condition;

        return $this;
    }

    public function hasDatabaseTransactions(): bool
    {
        return (bool) $this->evaluate($this->hasDatabaseTransactions);
    }

    public function beginDatabaseTransaction(): void
    {
        if (! $this->hasDatabaseTransactions()) {
            return;
        }

        $this->getDatabaseTransactionConnection()->beginTransaction();
    }

    public function commitDatabaseTransaction(): void
    {
        if (! $this->hasDatabaseTransactions()) {
            return;
        }

        $this->getDatabaseTransactionConnection()->commit();
    }

    public function rollBackDatabaseTransaction(): void
    {
        if (! $this->hasDatabaseTransactions()) {
            return;
        }

        $this->getDatabaseTransactionConnection()->rollBack();
    }

    protected function getDatabaseTransactionConnection(): Connection
    {
        $connection = $this->getTable()?->getQueryEntityManager()?->getConnection();

        if ($connection instanceof Connection) {
            return $connection;
        }

        $registry = $this->managerRegistry
            ?? throw new LogicException('databaseTransaction() needs the container-built action — register it as a service so the manager registry is injected.');

        $manager = $registry->getManager();

        if (! $manager instanceof EntityManagerInterface) {
            throw new LogicException(sprintf('databaseTransaction() needs an ORM entity manager, got %s.', get_debug_type($manager)));
        }

        return $manager->getConnection();
    }

    /**
     * The manager that owns a record's class — the table query's when the action
     * has a query-lane table, else the registry's.
     *
     * @param object|class-string $recordOrClass
     */
    protected function getEntityManagerForRecord(object|string $recordOrClass): EntityManagerInterface
    {
        $manager = $this->getTable()?->getQueryEntityManager();

        if ($manager instanceof EntityManagerInterface) {
            return $manager;
        }

        $class = is_object($recordOrClass) ? $recordOrClass::class : $recordOrClass;

        $registry = $this->managerRegistry
            ?? throw new LogicException(sprintf('The action [%s] persists a record, which needs the container-built instance — register it as a service so the manager registry is injected.', $this->getName()));

        $manager = $registry->getManagerForClass($class)
            ?? throw new LogicException(sprintf('No Doctrine manager handles %s.', $class));

        if (! $manager instanceof EntityManagerInterface) {
            throw new LogicException(sprintf('The action [%s] needs an ORM entity manager for %s, got %s.', $this->getName(), $class, get_debug_type($manager)));
        }

        return $manager;
    }
}
