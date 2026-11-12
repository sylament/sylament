<?php

declare(strict_types=1);

use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Schemas\Entity\OrderLine;

it('finds a composite-keyed record by its key, and only that record', function (): void {
    $em = $this->entityManager();
    $this->seed($line = new OrderLine(order: 7, line: 2), new OrderLine(order: 7, line: 3), new OrderLine(order: 8, line: 2));

    $query = $em->createQueryBuilder()->select('l')->from(OrderLine::class, 'l');
    $query->where(IdentifierConstraint::equals($query, 'l', OrderLine::class, RecordKey::of($em, $line), 'key'));

    expect($query->getQuery()->getResult())->sequence(fn ($l) => $l->toBeRecord(order: 7, line: 2));
});

it('refuses a malformed key without running a statement', function (string $key): void {
    $em = $this->entityManager();
    $query = $em->createQueryBuilder()->select('l')->from(OrderLine::class, 'l');

    expect(IdentifierConstraint::equals($query, 'l', OrderLine::class, $key, 'key'))->toBeNull()
        ->and($this->statementsRun())->toBeEmpty();
})->with([
    'leading zero' => '07~2',
    'not a number' => 'abc~2',
    'too few parts' => '7',
    'too many parts' => '7~2~1',
]);
