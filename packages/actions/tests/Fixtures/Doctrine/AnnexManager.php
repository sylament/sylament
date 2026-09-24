<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine;

use Doctrine\ORM\EntityManager;

/**
 * A second entity manager over the fixture mapping, on an in-memory database of
 * its own — the manager a table whose `query()` is built on it hands the
 * actions it hosts. One per container, so a test's requests share it.
 */
final class AnnexManager
{
    private ?EntityManager $entityManager = null;

    public function get(): EntityManager
    {
        return $this->entityManager ??= EntityManagerFactory::create();
    }
}
