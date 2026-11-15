<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Infrastructure\Entity\Tally;

it("records the test's statements, and neither the database's preparation nor a PRAGMA", function (): void {
    $this->entityManager()->getRepository(Tally::class)->count();

    expect($this->statementsRun())->toHaveCount(1)
        ->and($this->statementsRun()[0])->toStartWith('SELECT COUNT(*)');
});

it("answers what ran with Doctrine's minted names renumbered", function (): void {
    $this->entityManager()->createQuery('SELECT tally FROM ' . Tally::class . ' tally WHERE tally.count = 0')->getResult();

    expect($this->statementsRun())->toBe(['SELECT a0_.id AS id_0, a0_.count AS count_1 FROM Tally a0_ WHERE a0_.count = 0']);
});

it('counts from the last reset', function (): void {
    $this->entityManager()->getRepository(Tally::class)->count();
    $this->seed(new Tally());
    $this->entityManager()->getRepository(Tally::class)->count();

    expect($this->statementsRun())->toHaveCount(1);
});
