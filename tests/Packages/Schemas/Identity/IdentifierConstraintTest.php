<?php

declare(strict_types=1);

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\ParameterType;
use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Entity\AisleSeat;
use Sylament\Tests\Fixtures\Entity\HostPathRedirect;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\RawUuidCrate;
use Sylament\Tests\Fixtures\Entity\RawUuidPallet;
use Sylament\Tests\Fixtures\Entity\ShortlinkSlot;
use Sylament\Tests\Fixtures\Entity\StageResidency;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;
use Symfony\Component\Uid\Uuid;

describe('equals', function (): void {
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

    it('refuses a malformed key without running a statement', function (string $class, string $key): void {
        $em = $this->entityManager();
        $query = $em->createQueryBuilder()->select('l')->from($class, 'l');

        expect(IdentifierConstraint::equals($query, 'l', $class, $key, 'key'))->toBeNull()
            ->and($query->getParameters())->toBeEmpty()
            ->and($this->statementsRun())->toBeEmpty();
    })->with([
        'leading zero' => [OrderLine::class, '07~2'],
        'not a number' => [OrderLine::class, 'abc~2'],
        'too few parts' => [OrderLine::class, '7'],
        'too many parts' => [OrderLine::class, '7~2~1'],
        'a single integer with a leading zero' => [Product::class, '05'],
        'a single integer with a trailing word' => [Product::class, '5abc'],
        "an integer wider than PHP's" => [Product::class, '99999999999999999999'],
    ]);

    it('binds an integer wider than its column, which the platform refuses or matches no row for', function (): void {
        $this->seed(new ShortlinkSlot('sy', 7));

        $em = $this->entityManager();
        $query = $em->createQueryBuilder()->select('s')->from(ShortlinkSlot::class, 's');
        $query->where(IdentifierConstraint::equals($query, 's', ShortlinkSlot::class, 'sy~5000000000', 'key'));

        match (TestPlatform::current()) {
            TestPlatform::PostgreSql => expect(fn (): mixed => $query->getQuery()->getResult())->toThrow(DriverException::class),
            TestPlatform::Sqlite, TestPlatform::MySql => expect($query->getQuery()->getResult())->toBe([]),
        };
    });
});

describe('notEquals', function (): void {
    it('excludes the record its key names, and has nothing to exclude for a malformed key', function (object $line, object $sameHead, object $sameLine): void {
        $em = $this->entityManager();
        $this->seed($line, $sameHead, $sameLine);

        $query = $em->createQueryBuilder()->select('l')->from($line::class, 'l');
        $query->where(IdentifierConstraint::notEquals($query, 'l', $line::class, RecordKey::of($em, $line), 'key'));

        $malformed = $em->createQueryBuilder()->select('l')->from($line::class, 'l');

        expect(array_map(fn (object $found): string => RecordKey::of($em, $found), $query->getQuery()->getResult()))->toEqualCanonicalizing(['7~3', '8~2'])
            ->and(IdentifierConstraint::notEquals($malformed, 'l', $line::class, '07~2', 'key'))->toBeNull()
            ->and($malformed->getParameters())->toBeEmpty();
    })->with([
        'scalar fields' => fn (): array => [new OrderLine(order: 7, line: 2), new OrderLine(order: 7, line: 3), new OrderLine(order: 8, line: 2)],
        'an association plus a scalar' => fn (): array => [new InvoiceLine($seven = new Invoice(7), line: 2), new InvoiceLine($seven, line: 3), new InvoiceLine(new Invoice(8), line: 2)],
    ]);
});

describe('in', function (): void {
    it('selects the rows of a key list, binding a repeated key once and dropping a malformed one', function (object $line, object $sameHead, object $sameLine): void {
        $em = $this->entityManager();
        $this->seed($line, $sameHead, $sameLine);

        $query = $em->createQueryBuilder()->select('l')->from($line::class, 'l');
        $query->where(IdentifierConstraint::in($query, 'l', $line::class, ['7~2', '8~2', '7~2', '07~3'], 'keys'));

        expect(array_map(fn (object $found): string => RecordKey::of($em, $found), $query->getQuery()->getResult()))->toEqualCanonicalizing(['7~2', '8~2'])
            ->and($query->getParameters())->toHaveCount(4);
    })->with([
        'scalar fields' => fn (): array => [new OrderLine(order: 7, line: 2), new OrderLine(order: 7, line: 3), new OrderLine(order: 8, line: 2)],
        'an association plus a scalar' => fn (): array => [new InvoiceLine($seven = new Invoice(7), line: 2), new InvoiceLine($seven, line: 3), new InvoiceLine(new Invoice(8), line: 2)],
    ]);

    it('matches nothing for an empty list or one with no well-formed key', function (array $keys): void {
        $this->seed(new OrderLine(order: 7, line: 2));

        $query = $this->entityManager()->createQueryBuilder()->select('l')->from(OrderLine::class, 'l');
        $predicate = IdentifierConstraint::in($query, 'l', OrderLine::class, $keys, 'keys');

        expect($predicate)->toBe('1 = 0')
            ->and($query->where($predicate)->getQuery()->getResult())->toBe([]);
    })->with([
        'an empty list' => [[]],
        'only malformed keys' => [['07~2', 'seven']],
    ]);

    it('takes a list mixing the ints PHP made of numeric keys with strings', function (): void {
        $this->seed($first = new Product('first'), $second = new Product('second'), new Product('third'));

        $query = $this->entityManager()->createQueryBuilder()->select('p')->from(Product::class, 'p');
        $query->where(IdentifierConstraint::in($query, 'p', Product::class, [$first->id, (string) $second->id], 'keys'));

        expect(array_map(fn (Product $product): string => $product->name, $query->getQuery()->getResult()))->toEqualCanonicalizing(['first', 'second']);
    });

    it('runs a composite list of more keys than SQLite nests an expression deep', function (): void {
        $this->seed(new OrderLine(order: 7, line: 2), new OrderLine(order: 9, line: 4));

        $keys = array_map(static fn (int $line): string => "1~{$line}", range(1, 1_499));
        $query = $this->entityManager()->createQueryBuilder()->select('l')->from(OrderLine::class, 'l');
        $query->where(IdentifierConstraint::in($query, 'l', OrderLine::class, [...$keys, '7~2', '9~4'], 'keys'));

        expect($query->getQuery()->getResult())->toHaveCount(2);
    });

    it('refuses a parameter base a composite list already bound on the builder', function (): void {
        $query = $this->entityManager()->createQueryBuilder()->select('l')->from(OrderLine::class, 'l');
        IdentifierConstraint::in($query, 'l', OrderLine::class, ['7~2'], 'keys');

        expect(fn (): string => IdentifierConstraint::in($query, 'l', OrderLine::class, ['8~2'], 'keys'))
            ->toThrow(LogicException::class, 'The parameter base [keys] is already bound on this query builder');
    });

    it('refuses a parameter base ending in an index of its own', function (): void {
        $query = $this->entityManager()->createQueryBuilder()->select('l')->from(OrderLine::class, 'l');

        expect(fn (): string => IdentifierConstraint::in($query, 'l', OrderLine::class, ['7~2'], 'keys_1'))
            ->toThrow(LogicException::class, 'The parameter base [keys_1] ends in an index of its own');
    });

    it('binds a raw-bytes uuid as its sixteen bytes, which match its row', function (object $record, object $other, ParameterType|ArrayParameterType $type): void {
        $this->seed($record, $other);

        $em = $this->entityManager();
        $key = RecordKey::of($em, $em->getRepository($record::class)->findAll()[0]);
        $query = $em->createQueryBuilder()->select('r')->from($record::class, 'r');
        $query->where(IdentifierConstraint::in($query, 'r', $record::class, [$key], 'keys'));

        $parameter = $query->getParameters()->first();

        expect($parameter)->not->toBeFalse()
            ->and($parameter?->getType())->toBe($type)
            ->and(array_map(fn (object $found): string => RecordKey::of($em, $found), $query->getQuery()->getResult()))->toBe([$key]);
    })->with([
        'a single uuid, as an array of bytes' => fn (): array => [new RawUuidCrate(Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001')), new RawUuidCrate(Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000002')), ArrayParameterType::BINARY],
        'a uuid half, as bytes' => fn (): array => [new RawUuidPallet(Uuid::fromString('b1b2c3d4-0000-4000-8000-000000000001'), 'north'), new RawUuidPallet(Uuid::fromString('b1b2c3d4-0000-4000-8000-000000000001'), 'south'), ParameterType::BINARY],
    ]);
});

describe('notIn', function (): void {
    it('excludes the rows of a key list, and yields no predicate when nothing is left to exclude', function (object $line, object $sameHead, object $sameLine): void {
        $em = $this->entityManager();
        $this->seed($line, $sameHead, $sameLine);

        $query = $em->createQueryBuilder()->select('l')->from($line::class, 'l');
        $query->where(IdentifierConstraint::notIn($query, 'l', $line::class, ['7~2', '07~3'], 'keys'));

        $nothing = $em->createQueryBuilder()->select('l')->from($line::class, 'l');

        expect(array_map(fn (object $found): string => RecordKey::of($em, $found), $query->getQuery()->getResult()))->toEqualCanonicalizing(['7~3', '8~2'])
            ->and(IdentifierConstraint::notIn($nothing, 'l', $line::class, [], 'keys'))->toBeNull()
            ->and(IdentifierConstraint::notIn($nothing, 'l', $line::class, ['seven'], 'keys'))->toBeNull()
            ->and($nothing->getParameters())->toBeEmpty();
    })->with([
        'scalar fields' => fn (): array => [new OrderLine(order: 7, line: 2), new OrderLine(order: 7, line: 3), new OrderLine(order: 8, line: 2)],
        'an association plus a scalar' => fn (): array => [new InvoiceLine($seven = new Invoice(7), line: 2), new InvoiceLine($seven, line: 3), new InvoiceLine(new Invoice(8), line: 2)],
    ]);
});

describe('inColumns', function (): void {
    it('narrows a statement over one table by its identifier columns', function (array $seed, string $class, array $keys, string $predicate, int $count): void {
        $this->seed(...$seed);

        $em = $this->entityManager();
        $query = $em->getConnection()->createQueryBuilder()->select('COUNT(*)')->from($em->getClassMetadata($class)->getTableName());
        $sql = IdentifierConstraint::inColumns($em, $query, $class, $keys, 'keys');

        expect($sql)->toBe($predicate)
            ->and((int) $query->where($sql)->fetchOne())->toBe($count);
    })->with([
        'a single identifier' => fn (): array => [[new Product('first'), new Product('second')], Product::class, ['1', 'abc', '2', '2'], 'id IN (:keys)', 2],
        'scalar fields' => fn (): array => [[new OrderLine(order: 7, line: 2), new OrderLine(order: 7, line: 3)], OrderLine::class, ['7~2', '07~3'], '(order_number = :keys_0_0 AND line_number = :keys_0_1)', 1],
        'an association plus a scalar' => fn (): array => [[new InvoiceLine($seven = new Invoice(7), line: 2), new InvoiceLine($seven, line: 3)], InvoiceLine::class, ['7~2', '7~3'], '((invoice_id = :keys_0_0 AND line = :keys_0_1) OR (invoice_id = :keys_1_0 AND line = :keys_1_1))', 2],
        'no well-formed key' => fn (): array => [[new OrderLine(order: 7, line: 2)], OrderLine::class, ['07~2'], '1 = 0', 0],
    ]);

    it('binds a single identifier as an array of its own type, and each field of a composite one as its own type', function (): void {
        $em = $this->entityManager();
        $single = $em->getConnection()->createQueryBuilder();
        $pair = $em->getConnection()->createQueryBuilder();

        IdentifierConstraint::inColumns($em, $single, Product::class, ['1', '3', 'abc', '3'], 'keys');
        IdentifierConstraint::inColumns($em, $pair, HostPathRedirect::class, ['example=2Ecom~about'], 'keys');

        expect($single->getParameters())->toBe(['keys' => [1, 3]])
            ->and($single->getParameterTypes())->toBe(['keys' => ArrayParameterType::INTEGER])
            ->and($pair->getParameters())->toBe(['keys_0_0' => 'example.com', 'keys_0_1' => 'about'])
            ->and($pair->getParameterTypes())->toBe(['keys_0_0' => ParameterType::STRING, 'keys_0_1' => ParameterType::STRING]);
    });

    it('matches a raw-bytes uuid half only through the binary bind', function (): void {
        $this->seed(new RawUuidPallet($batch = Uuid::fromString('b1b2c3d4-0000-4000-8000-000000000001'), 'north'));

        $em = $this->entityManager();
        $connection = $em->getConnection();
        $query = $connection->createQueryBuilder()->select('COUNT(*)')->from('RawUuidPallet');
        $query->where(IdentifierConstraint::inColumns($em, $query, RawUuidPallet::class, ['b1b2c3d4-0000-4000-8000-000000000001~north'], 'keys'));

        $asText = $connection->fetchOne('SELECT COUNT(*) FROM RawUuidPallet WHERE batch = ? AND lane = ?', [$batch->toBinary(), 'north'], [ParameterType::STRING, ParameterType::STRING]);

        expect((int) $query->fetchOne())->toBe(1)
            ->and((int) $asText)->toBe(0);
    })->skip(TestPlatform::current() !== TestPlatform::Sqlite, "The control relies on SQLite's type affinity refusing bytes bound as text: MySQL matches them and PostgreSQL rejects them.");
});

describe('correlate', function (): void {
    it('correlates two aliases of one class on every identifier field', function (object $line, object $sameHead, object $sameLine): void {
        $em = $this->entityManager();
        $this->seed($line, $sameHead, $sameLine);

        $inner = $em->createQueryBuilder()->select('i')->from($line::class, 'i');
        $inner->where(IdentifierConstraint::correlate($inner, 'i', $line::class, 'o'))->andWhere("i.note = 'wanted'");

        $query = $em->createQueryBuilder()->select('o')->from($line::class, 'o')->where(sprintf('EXISTS (%s)', $inner->getDQL()));

        expect(array_map(fn (object $found): string => RecordKey::of($em, $found), $query->getQuery()->getResult()))->toBe(['7~2']);
    })->with([
        'scalar fields' => fn (): array => [new OrderLine(order: 7, line: 2, note: 'wanted'), new OrderLine(order: 7, line: 3), new OrderLine(order: 8, line: 2)],
        'an association plus a scalar' => fn (): array => [new InvoiceLine($seven = new Invoice(7), line: 2, note: 'wanted'), new InvoiceLine($seven, line: 3), new InvoiceLine(new Invoice(8), line: 2)],
    ]);
});

describe('groupBy', function (): void {
    it('groups a query by the bare path of every identifier field', function (array $seed, string $class, array $paths): void {
        $this->seed(...$seed);

        $query = $this->entityManager()->createQueryBuilder()->from($class, 'l');
        $query->select([...IdentifierConstraint::select($query, 'l', $class)->expressions, 'COUNT(l.note) AS notes']);

        foreach (IdentifierConstraint::groupBy($query, 'l', $class) as $path) {
            $query->addGroupBy($path);
        }

        expect(IdentifierConstraint::groupBy($query, 'l', $class))->toBe($paths)
            ->and($query->getQuery()->getScalarResult())->toHaveCount(3);
    })->with([
        'scalar fields' => fn (): array => [[new OrderLine(order: 7, line: 2), new OrderLine(order: 7, line: 3), new OrderLine(order: 8, line: 2)], OrderLine::class, ['l.order', 'l.line']],
        'an association plus a scalar' => fn (): array => [[new InvoiceLine($seven = new Invoice(7), line: 2), new InvoiceLine($seven, line: 3), new InvoiceLine(new Invoice(8), line: 2)], InvoiceLine::class, ['l.invoice', 'l.line']],
    ]);
});

describe('orderBy', function (): void {
    it('orders by the identifier as a tiebreak in the direction asked', function (object $line, object $sameHead, object $sameLine): void {
        $em = $this->entityManager();
        $this->seed($line, $sameHead, $sameLine);

        $query = $em->createQueryBuilder()->select('l')->from($line::class, 'l');
        IdentifierConstraint::orderBy($query, 'l', $line::class, 'desc');

        expect(array_map(fn (object $found): string => RecordKey::of($em, $found), $query->getQuery()->getResult()))->toBe(['8~2', '7~3', '7~2']);
    })->with([
        'scalar fields' => fn (): array => [new OrderLine(order: 7, line: 2), new OrderLine(order: 7, line: 3), new OrderLine(order: 8, line: 2)],
        'an association plus a scalar' => fn (): array => [new InvoiceLine($seven = new Invoice(7), line: 2), new InvoiceLine($seven, line: 3), new InvoiceLine(new Invoice(8), line: 2)],
    ]);

    it('appends only the identifier fields the query does not order by yet', function (string $class, string $ordered, string $direction, string $dql): void {
        $query = $this->entityManager()->createQueryBuilder()->select('l')->from($class, 'l')->orderBy($ordered, SortDirection::Descending);
        IdentifierConstraint::orderBy($query, 'l', $class, $direction);

        expect($query->getDQL())->toEndWith($dql);
    })->with([
        'a composite identifier, one field already ordered' => [OrderLine::class, 'l.line', 'desc', 'ORDER BY l.line DESC, l.order DESC'],
        'a single identifier already ordered' => [Product::class, 'l.id', 'asc', 'ORDER BY l.id DESC'],
        'another field first' => [Product::class, 'l.name', 'asc', 'ORDER BY l.name DESC, l.id ASC'],
    ]);
});

describe('chunk', function (): void {
    it('splits a key list into lists sized for one statement, each key once', function (string $class, array $keys, array $sizes): void {
        $chunks = IdentifierConstraint::chunk($this->entityManager(), $class, $keys);

        expect(array_map(count(...), $chunks))->toBe($sizes)
            ->and(array_merge(...$chunks))->toBe(array_values(array_unique(array_map(strval(...), $keys))));
    })->with([
        'a single identifier, 2 000 per statement' => [Product::class, range(1, 2_001), [2_000, 1]],
        'two fields, 500 per statement' => [OrderLine::class, array_map(static fn (int $line): string => "1~{$line}", range(1, 501)), [500, 1]],
        'three fields, 500 per statement' => [AisleSeat::class, array_map(static fn (int $seat): string => "main~1~{$seat}", range(1, 501)), [500, 1]],
        'repeated keys' => [OrderLine::class, ['1~2', '1~2', '1~3'], [2]],
        'an empty list' => [OrderLine::class, [], []],
    ]);
});

describe('batch', function (): void {
    it('batches a client-sized list whole for a single identifier and by the chunk rule for a composite one', function (string $class, array $keys, array $sizes): void {
        expect(array_map(count(...), IdentifierConstraint::batch($this->entityManager(), $class, $keys)))->toBe($sizes);
    })->with([
        'a single identifier' => [Product::class, range(1, 2_001), [2_001]],
        'a composite identifier' => [OrderLine::class, array_map(static fn (int $line): string => "1~{$line}", range(1, 501)), [500, 1]],
        'an empty list' => [OrderLine::class, [], []],
    ]);
});

describe('operations', function (): void {
    it('writes each operation in the DQL the contract states', function (string $class, array $keys, array $dql): void {
        $em = $this->entityManager();
        $builder = static fn () => $em->createQueryBuilder()->select('r')->from($class, 'r');
        $equals = $builder();
        $ordered = $builder();
        IdentifierConstraint::orderBy($ordered, 'r', $class, 'asc');

        expect([
            'equals' => IdentifierConstraint::equals($equals, 'r', $class, $keys[0], 'sylament_record_key'),
            'notEquals' => IdentifierConstraint::notEquals($builder(), 'r', $class, $keys[0], 'sylament_ignored_record'),
            'in' => IdentifierConstraint::in($builder(), 'r', $class, $keys, 'sylament_selected_record_keys'),
            'notIn' => IdentifierConstraint::notIn($builder(), 'r', $class, [$keys[0]], 'sylament_selected_record_keys'),
            'correlate' => IdentifierConstraint::correlate($builder(), 'i', $class, 'o'),
            'select' => IdentifierConstraint::select($builder(), 'r', $class)->expressions,
            'groupBy' => IdentifierConstraint::groupBy($builder(), 'r', $class),
            'orderBy' => mb_substr($ordered->getDQL(), (int) mb_strpos($ordered->getDQL(), 'ORDER BY')),
        ])->toBe($dql)
            ->and(array_map(static fn ($parameter): string => $parameter->getName(), $equals->getParameters()->toArray()))
            ->toBe(count($dql['groupBy']) === 1 ? ['sylament_record_key'] : ['sylament_record_key_0', 'sylament_record_key_1']);
    })->with([
        'a single identifier' => [Product::class, ['1', '2'], [
            'equals' => 'r.id = :sylament_record_key',
            'notEquals' => 'r.id <> :sylament_ignored_record',
            'in' => 'r.id IN (:sylament_selected_record_keys)',
            'notIn' => 'r.id NOT IN (:sylament_selected_record_keys)',
            'correlate' => 'i.id = o.id',
            'select' => ['r.id AS sy_key_0'],
            'groupBy' => ['r.id'],
            'orderBy' => 'ORDER BY r.id ASC',
        ]],
        'a scalar pair' => [HostPathRedirect::class, ['a~b', 'c~d'], [
            'equals' => 'r.host = :sylament_record_key_0 AND r.path = :sylament_record_key_1',
            'notEquals' => 'NOT (r.host = :sylament_ignored_record_0 AND r.path = :sylament_ignored_record_1)',
            'in' => '((r.host = :sylament_selected_record_keys_0_0 AND r.path = :sylament_selected_record_keys_0_1) OR (r.host = :sylament_selected_record_keys_1_0 AND r.path = :sylament_selected_record_keys_1_1))',
            'notIn' => 'NOT ((r.host = :sylament_selected_record_keys_0_0 AND r.path = :sylament_selected_record_keys_0_1))',
            'correlate' => 'i.host = o.host AND i.path = o.path',
            'select' => ['r.host AS sy_key_0', 'r.path AS sy_key_1'],
            'groupBy' => ['r.host', 'r.path'],
            'orderBy' => 'ORDER BY r.host ASC, r.path ASC',
        ]],
        'a derived identity' => [StageResidency::class, ['main-hall~autumn', 'main-hall~spring'], [
            'equals' => 'r.venue = :sylament_record_key_0 AND r.season = :sylament_record_key_1',
            'notEquals' => 'NOT (r.venue = :sylament_ignored_record_0 AND r.season = :sylament_ignored_record_1)',
            'in' => '((r.venue = :sylament_selected_record_keys_0_0 AND r.season = :sylament_selected_record_keys_0_1) OR (r.venue = :sylament_selected_record_keys_1_0 AND r.season = :sylament_selected_record_keys_1_1))',
            'notIn' => 'NOT ((r.venue = :sylament_selected_record_keys_0_0 AND r.season = :sylament_selected_record_keys_0_1))',
            'correlate' => 'i.venue = o.venue AND i.season = o.season',
            'select' => ['IDENTITY(r.venue) AS sy_key_0', 'r.season AS sy_key_1'],
            'groupBy' => ['r.venue', 'r.season'],
            'orderBy' => 'ORDER BY r.venue ASC, r.season ASC',
        ]],
    ]);
});
