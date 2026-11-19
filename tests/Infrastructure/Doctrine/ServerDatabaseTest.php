<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\Tally;
use Sylament\Tests\Infrastructure\Doctrine\ServerDatabase;
use Sylament\Tests\Infrastructure\Doctrine\TestDatabase;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;

it('empties every table and restarts its identity counter before the next test', function (): void {
    $this->seed(new Tally(), new Tally(), new Tally());

    TestDatabase::testStarted();

    $fresh = new Tally();
    $this->seed($fresh);

    expect($this->entityManager()->getRepository(Tally::class)->count())->toBe(1)
        ->and($fresh->id)->toBe(1);
})->skip(TestPlatform::current() === TestPlatform::Sqlite, "Only a server database is wiped; SQLite copies a template over the run's file.");

it('restarts a counter a rolled-back insert moved, though its table stayed empty', function (): void {
    $connection = $this->entityManager()->getConnection();
    $connection->beginTransaction();
    $connection->executeStatement('INSERT INTO Tally (count) VALUES (0)');
    $connection->rollBack();

    TestDatabase::testStarted();

    $fresh = new Tally();
    $this->seed($fresh);

    expect($fresh->id)->toBe(1);
})->skip(TestPlatform::current() === TestPlatform::Sqlite, "Only a server database is wiped; SQLite copies a template over the run's file.");

it("refuses a server url that names no database, since it derives the run's database from that name", function (string $url): void {
    expect(fn (): ServerDatabase => ServerDatabase::fromUrl($url))
        ->toThrow(LogicException::class, 'SYLAMENT_TEST_DATABASE_URL must name a database the run derives its own from');
})->with([
    'no path' => ['mysql://root:secret@127.0.0.1:33307'],
    'an empty path' => ['postgresql://postgres:secret@127.0.0.1:55433/'],
]);
