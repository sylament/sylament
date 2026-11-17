<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

/**
 * A second entity manager over the world's connection and mapping, built once per
 * container: the manager a table whose query runs on it hands the actions it hosts,
 * which the registry never serves.
 */
final class SecondManager
{
    private ?EntityManager $entityManager = null;

    public function __construct(
        private readonly EntityManagerInterface $registryManager,
    ) {
    }

    public function get(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $eventManager = $this->registryManager->getEventManager();

        if (!$eventManager instanceof EventManager) {
            throw new LogicException(sprintf("The registry's manager dispatches through a [%s], not an event manager.", $eventManager::class));
        }

        return $this->entityManager = new EntityManager($this->registryManager->getConnection(), $this->registryManager->getConfiguration(), $eventManager);
    }
}
