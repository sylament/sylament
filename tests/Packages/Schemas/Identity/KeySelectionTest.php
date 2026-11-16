<?php

declare(strict_types=1);

use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Entity\HexCodedSwatch;
use Sylament\Tests\Fixtures\Entity\HexCodedTile;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\MarkedCodeLabel;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\RawUuidCrate;
use Sylament\Tests\Fixtures\Entity\ShelfPlacement;
use Sylament\Tests\Fixtures\Entity\ShelfZone;
use Sylament\Tests\Fixtures\Entity\StageResidency;
use Sylament\Tests\Fixtures\Entity\StageVenue;
use Symfony\Component\Uid\Uuid;

describe('keyOf', function (): void {
    it('keys a scalar row as RecordKey::of() keys the record it reads', function (object $record, string $key): void {
        $this->seed($record);

        $em = $this->entityManager();
        $query = $em->createQueryBuilder()->from($record::class, 'r');
        $selection = IdentifierConstraint::select($query, 'r', $record::class);
        $rows = $query->select($selection->expressions)->getQuery()->getScalarResult();

        expect(array_map($selection->keyOf(...), $rows))->toBe([$key])
            ->and(RecordKey::of($em, $em->getRepository($record::class)->findAll()[0]))->toBe($key);
    })->with([
        'scalar fields' => fn (): array => [new OrderLine(order: 7, line: 2), '7~2'],
        'an association plus a scalar' => fn (): array => [new InvoiceLine(new Invoice(7), line: 2), '7~2'],
        'an enum half' => fn (): array => [new ShelfPlacement(ShelfZone::Footer, 2), 'footer~2'],
        'an association onto a string-keyed target' => fn (): array => [new StageResidency(new StageVenue('main-hall'), 'autumn/2026'), 'main-hall~autumn=2F2026'],
        'an association onto a converting target' => fn (): array => [new HexCodedTile(new HexCodedSwatch('ff'), 'north'), 'ff~north'],
        'an object identifier' => fn (): array => [new RawUuidCrate(Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001')), 'a1b2c3d4-0000-4000-8000-000000000001'],
    ]);

    it('refuses a row read through another selection, naming the column it lacks', function (): void {
        $query = $this->entityManager()->createQueryBuilder()->from(OrderLine::class, 'l');
        $selection = IdentifierConstraint::select($query, 'l', OrderLine::class, 'line_key');

        expect(fn (): string => $selection->keyOf(['sy_key_0' => 7, 'sy_key_1' => 2]))
            ->toThrow(LogicException::class, sprintf('A result row of [%s] lacks the key column [line_key_0], so it was not read through the selection that keys it.', OrderLine::class));
    });
});

describe('keyOfHydrated', function (): void {
    it('keys an array row as RecordKey::of() keys its record, reading no field a second time', function (object $record, string $key): void {
        $this->seed($record);

        $em = $this->entityManager();
        $query = $em->createQueryBuilder()->from($record::class, 'r');
        $selection = IdentifierConstraint::select($query, 'r', $record::class);
        $rows = $query->select($selection->expressions)->getQuery()->getArrayResult();

        expect(array_map($selection->keyOfHydrated(...), $rows))->toBe([$key])
            ->and(RecordKey::of($em, $em->getRepository($record::class)->findAll()[0]))->toBe($key);
    })->with([
        'scalar fields' => fn (): array => [new OrderLine(order: 7, line: 2), '7~2'],
        'an association plus a scalar' => fn (): array => [new InvoiceLine(new Invoice(7), line: 2), '7~2'],
        'an enum half' => fn (): array => [new ShelfPlacement(ShelfZone::Footer, 2), 'footer~2'],
        'a type whose reader is not idempotent' => fn (): array => [new HexCodedSwatch('ff'), 'ff'],
        'a type whose reader refuses what it has read' => fn (): array => [new MarkedCodeLabel('north'), 'north'],
        'an association onto a converting target' => fn (): array => [new HexCodedTile(new HexCodedSwatch('ff'), 'north'), 'ff~north'],
    ]);
});
