<?php

declare(strict_types=1);

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Sylament\Tests\Fixtures\Infrastructure\Entity\Tally;
use Sylament\Tests\Infrastructure\Doctrine\FailingWrite;
use Sylament\Tests\Infrastructure\Doctrine\TestDatabase;

it('fails the delete past the allowance, and the transaction undoes the deletes before it', function (): void {
    $this->seed(new Tally(), new Tally(), new Tally());
    FailingWrite::afterDeletes($this->entityManager(), Tally::class, 2);
    $connection = $this->entityManager()->getConnection();

    expect(fn (): mixed => $connection->transactional(static function (Connection $connection): void {
        foreach ([1, 2, 3] as $id) {
            $connection->delete('Tally', ['id' => $id]);
        }
    }))->toThrow(DriverException::class, FailingWrite::MESSAGE)
        ->and($this->entityManager()->getRepository(Tally::class)->count())->toBe(3);
});

it('counts rows rather than statements, so one statement deleting past the allowance fails whole', function (): void {
    $this->seed(new Tally(), new Tally(), new Tally());
    FailingWrite::afterDeletes($this->entityManager(), Tally::class, 2);
    $connection = $this->entityManager()->getConnection();

    expect(fn (): int|string => $connection->executeStatement('DELETE FROM Tally'))
        ->toThrow(DriverException::class, FailingWrite::MESSAGE)
        ->and($this->entityManager()->getRepository(Tally::class)->count())->toBe(3);
});

it('leaves nothing behind for the next test', function (): void {
    $this->seed(new Tally());
    FailingWrite::afterDeletes($this->entityManager(), Tally::class, 0);

    TestDatabase::testStarted();

    $this->seed(new Tally(), new Tally());

    expect($this->entityManager()->getConnection()->executeStatement('DELETE FROM Tally'))->toBe(2);
});
