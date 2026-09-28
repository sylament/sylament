<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine;

use Doctrine\ORM\EntityManager;
use Sylament\Testing\Doctrine\RecordingMiddleware;
use Sylament\Testing\Doctrine\TestEntityManagers;

final class EntityManagerFactory
{
    /**
     * The fixture schema on the test database, which is in-memory SQLite unless
     * SYLAMENT_TEST_DATABASE_URL names a server, counting every statement it runs
     * when given a recorder.
     */
    public static function create(?RecordingMiddleware $recorder = null): EntityManager
    {
        return TestEntityManagers::create('forms', [__DIR__ . '/Entity'], [EmblemCodeType::NAME => EmblemCodeType::class], $recorder);
    }

    /**
     * Clears the identity map after flushing, so a subsequent read goes to the
     * database rather than answering from memory.
     */
    public static function seed(EntityManager $entityManager, object ...$entities): void
    {
        foreach ($entities as $entity) {
            $entityManager->persist($entity);
        }

        $entityManager->flush();
        $entityManager->clear();
    }
}
