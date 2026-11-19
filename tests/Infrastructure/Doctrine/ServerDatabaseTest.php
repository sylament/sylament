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

it("names a parallel worker's database apart from every run's and every other worker's", function (): void {
    $before = ['SYLAMENT_TEST_RUN' => getenv('SYLAMENT_TEST_RUN'), 'TEST_TOKEN' => getenv('TEST_TOKEN')];
    $name = static function (string $run, ?string $token): string {
        putenv('SYLAMENT_TEST_RUN=' . $run);
        putenv($token === null ? 'TEST_TOKEN' : 'TEST_TOKEN=' . $token);

        return ServerDatabase::fromUrl('mysql://root:secret@127.0.0.1:33307/sylament_test')->name();
    };

    try {
        $names = [$name('x_1', null), $name('x', '1'), $name('x_1', '1'), $name('x', '11')];
    } finally {
        foreach ($before as $variable => $value) {
            putenv($value === false ? $variable : $variable . '=' . $value);
        }
    }

    expect($names)->toBe(['sylament_test_x_1', 'sylament_test_x-1', 'sylament_test_x_1-1', 'sylament_test_x-11']);
});
