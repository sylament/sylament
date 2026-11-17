<?php

declare(strict_types=1);

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\Tally;
use Sylament\Tests\Infrastructure\Doctrine\DriverValues;
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

it("fails a many-to-many's join-row delete past the allowance, which no entity maps", function (): void {
    $garden = new Garden();
    $garden->addPlant($rock = new Plant('Rock'));
    $garden->addPlant($moss = new Plant('Moss'));
    $this->seed($garden, $rock, $moss);
    FailingWrite::afterJoinTableDeletes($this->entityManager(), Garden::class, 'plants', 1);
    $connection = $this->entityManager()->getConnection();

    expect(fn (): mixed => $connection->transactional(static function (Connection $connection) use ($rock, $moss): void {
        foreach ([$rock, $moss] as $plant) {
            $connection->delete('garden_plant', ['plant_id' => $plant->id]);
        }
    }))->toThrow(DriverException::class, FailingWrite::MESSAGE)
        ->and(DriverValues::number($connection->fetchOne('SELECT COUNT(*) FROM garden_plant')))->toBe(2);
});

it('leaves nothing behind for the next test', function (): void {
    $this->seed(new Tally());
    FailingWrite::afterDeletes($this->entityManager(), Tally::class, 0);

    TestDatabase::testStarted();

    $this->seed(new Tally(), new Tally());

    expect($this->entityManager()->getConnection()->executeStatement('DELETE FROM Tally'))->toBe(2);
});
