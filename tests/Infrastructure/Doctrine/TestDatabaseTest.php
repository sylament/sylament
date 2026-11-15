<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Infrastructure\Entity\Tally;
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

it('does not wait on a transaction the previous test left open', function (): void {
    $connection = $this->entityManager()->getConnection();
    $connection->beginTransaction();
    $connection->executeStatement('INSERT INTO Tally (count) VALUES (0)');

    TestDatabase::testStarted();

    $started = hrtime(true);

    expect($this->entityManager()->getRepository(Tally::class)->count())->toBe(0)
        ->and((hrtime(true) - $started) / 1e9)->toBeLessThan(5.0);
});
