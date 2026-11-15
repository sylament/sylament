<?php

declare(strict_types=1);

use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Schemas\Entity\Invoice;
use Sylament\Tests\Fixtures\Schemas\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Schemas\Entity\OrderLine;

it('finds a composite-keyed record by its key, and only that record', function (object $line, object $sameHead, object $sameLine): void {
    $em = $this->entityManager();
    $this->seed($line, $sameHead, $sameLine);

    $query = $em->createQueryBuilder()->select('l')->from($line::class, 'l');
    $query->where(IdentifierConstraint::equals($query, 'l', $line::class, RecordKey::of($em, $line), 'key'));

    expect($query->getQuery()->getResult())->sequence(fn ($found) => $found->toBeRecord(line: 2, note: 'wanted'));
})->with([
    'scalar fields' => fn (): array => [new OrderLine(order: 7, line: 2, note: 'wanted'), new OrderLine(order: 7, line: 3), new OrderLine(order: 8, line: 2)],
    'an association plus a scalar' => fn (): array => [new InvoiceLine($seven = new Invoice(7), line: 2, note: 'wanted'), new InvoiceLine($seven, line: 3), new InvoiceLine(new Invoice(8), line: 2)],
]);

it('refuses a malformed key without running a statement', function (string $key): void {
    $em = $this->entityManager();
    $query = $em->createQueryBuilder()->select('l')->from(OrderLine::class, 'l');

    expect(IdentifierConstraint::equals($query, 'l', OrderLine::class, $key, 'key'))->toBeNull()
        ->and($query->getParameters())->toBeEmpty()
        ->and($this->statementsRun())->toBeEmpty();
})->with([
    'leading zero' => '07~2',
    'not a number' => 'abc~2',
    'too few parts' => '7',
    'too many parts' => '7~2~1',
]);
