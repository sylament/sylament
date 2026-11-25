<?php

declare(strict_types=1);

use Doctrine\Persistence\Mapping\MappingException;
use Sylament\Tests\Fixtures\Entity\Tally;
use Sylament\Tests\Fixtures\SecondaryEntity\Observatory;
use Sylament\Tests\Infrastructure\Doctrine\TestDatabase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

it("keeps a test's rows across requests through a rebooting client", function (): void {
    $tally = new Tally();
    $this->seed($tally);

    $client = $this::getContainer()->get('test.client');
    assert($client instanceof KernelBrowser);
    $client->catchExceptions(false);

    $client->request('POST', '/_tests/tally/' . $tally->id);
    $client->request('POST', '/_tests/tally/' . $tally->id);

    expect($this->reload($tally)->count)->toBe(2);
});

it('starts the next test on an empty database with fresh identity counters', function (): void {
    expect($this->entityManager()->getRepository(Tally::class)->count())->toBe(0);

    $tally = new Tally();
    $this->seed($tally);

    expect($tally->id)->toBe(1);
});

it('rolls back a transaction the previous test left open', function (): void {
    $connection = $this->entityManager()->getConnection();
    $connection->beginTransaction();
    $connection->executeStatement('INSERT INTO Tally (count) VALUES (0)');

    TestDatabase::testStarted();

    expect($this->entityManager()->getRepository(Tally::class)->count())->toBe(0);
});

it('serves an entity only the secondary manager maps on its own connection, starting empty', function (): void {
    $this->seed($observatory = new Observatory('Kitt Peak'));

    expect($this->entityManagerFor(Observatory::class))->not->toBe($this->entityManager())
        ->and($this->entityManagerFor(Observatory::class)->getConnection())->not->toBe($this->entityManager()->getConnection())
        ->and(fn (): mixed => $this->entityManager()->getClassMetadata(Observatory::class))->toThrow(MappingException::class)
        ->and($observatory->id)->toBe(1)
        ->and($this->reload($observatory)->name)->toBe('Kitt Peak');
});
