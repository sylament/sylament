<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\Concerns;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Sylament\Tests\Infrastructure\Doctrine\RecordingMiddleware;

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
     * Reads the record's row again by its identifier, through the current manager,
     * even when that manager already holds the record.
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
        $reloaded = $entityManager->find($metadata->getName(), $metadata->getIdentifierValues($entity))
            ?? throw new LogicException(sprintf('No [%s] row has the identifier of the record given.', $metadata->getName()));

        $entityManager->refresh($reloaded);

        if (!$reloaded instanceof $entity) {
            throw new LogicException(sprintf('Reloading a [%s] answered a [%s].', $entity::class, $reloaded::class));
        }

        return $reloaded;
    }

    /**
     * The statements the default connection ran since the last {@see seed()}, with
     * Doctrine's minted names renumbered.
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
