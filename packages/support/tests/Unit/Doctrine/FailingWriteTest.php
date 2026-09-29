<?php

declare(strict_types=1);

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManager;
use Sylament\Support\Tests\Doctrine\DatabasePool;
use Sylament\Support\Tests\Doctrine\FailingWrite;
use Sylament\Support\Tests\Doctrine\TestEntityManagers;
use Sylament\Support\Tests\Doctrine\TestPlatform;
use Sylament\Support\Tests\Fixtures\Doctrine\Entity\Shelf;

function shelvesLabelled(string ...$labels): EntityManager
{
    $entityManager = TestEntityManagers::create('support', [dirname(__DIR__, 2) . '/Fixtures/Doctrine/Entity']);

    foreach ($labels as $label) {
        $entityManager->persist(new Shelf($label));
    }

    $entityManager->flush();
    $entityManager->clear();

    return $entityManager;
}

it('fails the delete past the allowance and lets the transaction undo the ones before it', function (): void {
    $entityManager = shelvesLabelled('a', 'b', 'c');
    FailingWrite::afterDeletes($entityManager, Shelf::class, 2);
    $connection = $entityManager->getConnection();

    expect(fn () => $connection->transactional(static function (Connection $connection): void {
        foreach (['a', 'b', 'c'] as $label) {
            $connection->executeStatement('DELETE FROM Shelf WHERE label = ?', [$label]);
        }
    }))->toThrow(DriverException::class, FailingWrite::MESSAGE)
        ->and($connection->fetchOne('SELECT COUNT(*) FROM Shelf'))->toEqual(3);
});

it('counts rows rather than statements, so one statement past the allowance fails whole', function (): void {
    $entityManager = shelvesLabelled('a', 'b', 'c');
    FailingWrite::afterDeletes($entityManager, Shelf::class, 2);
    $connection = $entityManager->getConnection();

    expect(fn (): int|string => $connection->executeStatement('DELETE FROM Shelf'))
        ->toThrow(DriverException::class, FailingWrite::MESSAGE)
        ->and($connection->fetchOne('SELECT COUNT(*) FROM Shelf'))->toEqual(3);
});

it('leaves nothing behind for the next test that takes the database', function (): void {
    FailingWrite::afterDeletes(shelvesLabelled('a'), Shelf::class, 0);

    DatabasePool::instance()->testStarted();

    expect(shelvesLabelled('b', 'c')->getConnection()->executeStatement('DELETE FROM Shelf'))->toEqual(2);
})->skip(TestPlatform::current() === TestPlatform::Sqlite, 'Only a pooled server database outlives its test; an in-memory SQLite one goes with its manager.');
