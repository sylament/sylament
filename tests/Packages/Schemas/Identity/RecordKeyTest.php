<?php

declare(strict_types=1);

use Random\Engine\Mt19937;
use Random\Randomizer;
use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Identity\IdentifierFields;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Entity\AisleSeat;
use Sylament\Tests\Fixtures\Entity\Charter;
use Sylament\Tests\Fixtures\Entity\CharterBerth;
use Sylament\Tests\Fixtures\Entity\CharterSeal;
use Sylament\Tests\Fixtures\Entity\CharterSealClause;
use Sylament\Tests\Fixtures\Entity\CipherStrongbox;
use Sylament\Tests\Fixtures\Entity\CipherVault;
use Sylament\Tests\Fixtures\Entity\CodeKeyedSlate;
use Sylament\Tests\Fixtures\Entity\HexCodedSwatch;
use Sylament\Tests\Fixtures\Entity\HostPathRedirect;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\RackSlot;
use Sylament\Tests\Fixtures\Entity\RackTier;
use Sylament\Tests\Fixtures\Entity\RawUuidCrate;
use Sylament\Tests\Fixtures\Entity\RawUuidPallet;
use Sylament\Tests\Fixtures\Entity\RevisionedNote;
use Sylament\Tests\Fixtures\Entity\ShelfPlacement;
use Sylament\Tests\Fixtures\Entity\ShelfZone;
use Sylament\Tests\Fixtures\Entity\ShortlinkSlot;
use Sylament\Tests\Fixtures\Entity\StageResidency;
use Sylament\Tests\Fixtures\Entity\StageVenue;
use Sylament\Tests\Fixtures\Entity\StatuePlinth;
use Sylament\Tests\Fixtures\Entity\SubtypedVoucher;
use Sylament\Tests\Fixtures\Entity\UidReceipt;
use Sylament\Tests\Fixtures\Schemas\Doctrine\Cipher;
use Sylament\Tests\Fixtures\Schemas\Record\UnmappedHostPathRedirect;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

describe('of', function (): void {
    it('mints one key for a record before it is saved and once it is loaded', function (array $seed, object $record, string $key): void {
        $em = $this->entityManager();
        $minted = RecordKey::of($em, $record);

        $this->seed(...$seed);

        expect($minted)->toBe($key)
            ->and(RecordKey::of($this->entityManager(), $this->reload($record)))->toBe($key);
    })->with([
        'a scalar pair, in declaration order' => fn (): array => [[$line = new OrderLine(order: 7, line: 2)], $line, '7~2'],
        'an association plus a scalar' => fn (): array => [[$line = new InvoiceLine(new Invoice(7), line: 2)], $line, '7~2'],
        'a derived identity onto a string-keyed parent' => fn (): array => [[$residency = new StageResidency(new StageVenue('main-hall'), 'autumn/2026')], $residency, 'main-hall~autumn=2F2026'],
        'a single derived identity' => fn (): array => [[$charter = new Charter(1), $seal = new CharterSeal($charter)], $seal, '1'],
        'a single string identifier, verbatim' => fn (): array => [[$slate = new CodeKeyedSlate('sy/0.1')], $slate, 'sy/0.1'],
    ]);

    it('keys a chained derived identity once its target is managed, as before it was saved', function (): void {
        $charter = new Charter(1);
        $seal = new CharterSeal($charter);
        $clause = new CharterSealClause($seal, 'preamble');

        $unsaved = RecordKey::of($this->entityManager(), $clause);

        $this->seed($charter, $seal, $clause);

        $em = $this->entityManager();
        $loaded = $em->getRepository(CharterSealClause::class)->findAll();

        expect($unsaved)->toBe('1~preamble')
            ->and(array_map(fn (CharterSealClause $clause): string => RecordKey::of($em, $clause), $loaded))->toBe(['1~preamble'])
            ->and(RecordKey::find($em, $loaded[0]))->toBe('1~preamble');
    });

    it('refuses a record with a component still missing, naming the record', function (object $record): void {
        expect(fn (): string => RecordKey::of($this->entityManager(), $record))
            ->toThrow(LogicException::class, sprintf('Object records must expose a readable non-blank identifier; [%s] has none set.', $record::class));
    })->with([
        'a generated identifier before the first save' => fn (): Product => new Product('unsaved'),
        'a half-filled composite' => fn (): AisleSeat => new AisleSeat('main', 3),
        'a derived identity whose chain is broken' => fn (): CharterSealClause => new CharterSealClause(new CharterSeal(new Charter(null)), 'preamble'),
    ]);

    it('keys a subclass the mapping does not know by the class the caller declares, and names that class when it cannot', function (): void {
        $em = $this->entityManager();

        expect(RecordKey::of($em, new UnmappedHostPathRedirect('example.com', '/about'), HostPathRedirect::class))->toBe('example=2Ecom~=2Fabout')
            ->and(fn (): string => RecordKey::of($em, new UnmappedHostPathRedirect('example.com', ' '), HostPathRedirect::class))
            ->toThrow(LogicException::class, sprintf('The identifier of [%s] is not a printable value. It holds host "example.com", path " ".', HostPathRedirect::class));
    });

    it('refuses a stored row whose string component is blank, naming the row', function (object $record, string $message): void {
        $this->seed($record);

        $em = $this->entityManager();
        $loaded = $em->getRepository($record::class)->findAll();

        expect($loaded)->toHaveCount(1)
            ->and(fn (): string => RecordKey::of($em, $loaded[0]))->toThrow(LogicException::class, $message);
    })->with([
        'a blank half of a pair' => fn (): array => [new HostPathRedirect('SKU-1', ''), sprintf('The identifier of [%s] is not a printable value. It holds host "SKU-1", path "".', HostPathRedirect::class)],
        'a blank single string' => fn (): array => [new CodeKeyedSlate(''), sprintf('The identifier of [%s] is not a printable value. It holds code "".', CodeKeyedSlate::class)],
    ]);
});

describe('find', function (): void {
    it('answers no key while a component is missing or blank', function (object $record): void {
        expect(RecordKey::find($this->entityManager(), $record))->toBeNull();
    })->with([
        'a generated identifier before the first save' => fn (): Product => new Product('unsaved'),
        'a half-filled composite' => fn (): AisleSeat => new AisleSeat('main', 3),
        'a derived identity whose chain is broken' => fn (): CharterSealClause => new CharterSealClause(new CharterSeal(new Charter(null)), 'preamble'),
        'a whitespace string' => fn (): HostPathRedirect => new HostPathRedirect('example.com', '  '),
        'a value object printing blank' => fn (): CipherVault => new CipherVault(new Cipher(' '), 'north'),
    ]);

    it('keys an unsaved record whose assigned identifier is filled, and the same once it is saved', function (object $record): void {
        $unsaved = RecordKey::find($this->entityManager(), $record);

        $this->seed($record);

        expect($unsaved)->toBe('7~2')
            ->and(RecordKey::find($this->entityManager(), $this->reload($record)))->toBe('7~2');
    })->with([
        'scalar fields' => fn (): OrderLine => new OrderLine(order: 7, line: 2),
        'an association plus a scalar' => fn (): InvoiceLine => new InvoiceLine(new Invoice(7), line: 2),
    ]);
});

describe('fromValues', function (): void {
    it('prints values already in their PHP form, as the record holding them mints', function (string $class, array $values, object $record): void {
        $em = $this->entityManager();

        expect(RecordKey::fromValues($em, $class, $values))->toBe(RecordKey::of($em, $record));
    })->with([
        'scalar fields' => fn (): array => [OrderLine::class, [7, 2], new OrderLine(order: 7, line: 2)],
        'an association plus a scalar' => fn (): array => [InvoiceLine::class, [7, 2], new InvoiceLine(new Invoice(7), line: 2)],
        'an enum half' => fn (): array => [ShelfPlacement::class, [ShelfZone::Footer, 2], new ShelfPlacement(ShelfZone::Footer, 2)],
    ]);

    it('converts nothing, so a spelling its type would rewrite is printed as given', function (): void {
        $em = $this->entityManager();
        $token = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

        expect(RecordKey::fromValues($em, SubtypedVoucher::class, [$token, '0007', '12.25', true]))->toBe("{$token}~0007~12=2E25~1")
            ->and(RecordKey::of($em, new SubtypedVoucher($token, '0007', '12.25', true)))->toBe("{$token}~7~12=2E25~1");
    });

    it('refuses values that do not match the identifier', function (): void {
        expect(fn (): string => RecordKey::fromValues($this->entityManager(), OrderLine::class, [7]))
            ->toThrow(LogicException::class, sprintf('A key of [%s] takes 2 identifier values, 1 given.', OrderLine::class));
    });
});

describe('toIdentifier', function (): void {
    it('decodes a key into the map the entity manager finds its record by', function (object $record, string $key, array $identifier): void {
        $this->seed($record);

        $em = $this->entityManager();
        $decoded = RecordKey::toIdentifier($em, $record::class, $key);
        $found = $em->find($record::class, $decoded ?? []);

        expect($decoded)->toBe($identifier)
            ->and($found)->not->toBeNull()
            ->and(RecordKey::of($em, $found))->toBe($key);
    })->with([
        'scalar fields' => fn (): array => [new OrderLine(order: 7, line: 2), '7~2', ['order' => 7, 'line' => 2]],
        'an association plus a scalar' => fn (): array => [new InvoiceLine(new Invoice(7), line: 2), '7~2', ['invoice' => 7, 'line' => 2]],
        'escaped strings' => fn (): array => [new HostPathRedirect('www.example.com', '/blog/all the way'), 'www=2Eexample=2Ecom~=2Fblog=2Fall=20the=20way', ['host' => 'www.example.com', 'path' => '/blog/all the way']],
        'a single string, verbatim' => fn (): array => [new CodeKeyedSlate('sy/0.1~x'), 'sy/0.1~x', ['code' => 'sy/0.1~x']],
    ]);

    it('answers null rather than throwing for a key no record of the class mints', function (string $class, string $key): void {
        expect(RecordKey::toIdentifier($this->entityManager(), $class, $key))->toBeNull();
    })->with([
        'a leading zero' => [OrderLine::class, '07~2'],
        'not a number' => [OrderLine::class, 'abc~2'],
        'too few parts' => [OrderLine::class, '7'],
        'too many parts' => [OrderLine::class, '7~2~1'],
        'an empty component' => [HostPathRedirect::class, '~about'],
        'an unescaped character' => [HostPathRedirect::class, 'example.com~about'],
        'a lower-case escape' => [HostPathRedirect::class, 'example=2ecom~about'],
        'an escaped safe character' => [HostPathRedirect::class, 'ex=61mple~about'],
        'an escape that is not UTF-8' => [HostPathRedirect::class, 'ex=FFmple~about'],
        'a single integer with a leading zero' => [Product::class, '05'],
        'a single integer with a trailing word' => [Product::class, '5abc'],
        'a single integer with a trailing newline' => [Product::class, "5\n"],
        'blank' => [Product::class, ' '],
    ]);

    it('decodes no key of a type that refuses its printed form on read and on write', function (object $record, string $key): void {
        $this->seed($record);

        $em = $this->entityManager();
        $loaded = $em->getRepository($record::class)->findAll()[0];

        expect(RecordKey::of($em, $loaded))->toBe($key)
            ->and(RecordKey::toIdentifier($em, $record::class, $key))->toBeNull();
    })->with([
        'a single cipher' => fn (): array => [new CipherStrongbox(new Cipher('omega')), 'omega'],
        'a cipher half' => fn (): array => [new CipherVault(new Cipher('zeta'), 'north'), 'zeta~north'],
    ]);
});

describe('values', function (): void {
    it("reads each identifier value as its field holds it, an association reduced to its target's", function (object $record, array $values): void {
        expect(RecordKey::values($this->entityManager(), $record))->toEqual($values);
    })->with([
        'an association plus a scalar' => fn (): array => [new InvoiceLine(new Invoice(7), line: 2), [7, 2]],
        'a chained derived identity' => fn (): array => [new CharterSealClause(new CharterSeal(new Charter(1)), 'preamble'), [1, 'preamble']],
        'an object half' => fn (): array => [new RawUuidPallet(Uuid::fromString('b1b2c3d4-0000-4000-8000-000000000001'), 'north'), [Uuid::fromString('b1b2c3d4-0000-4000-8000-000000000001'), 'north']],
        'an enum half, as its backing value' => fn (): array => [new ShelfPlacement(ShelfZone::Footer, 2), ['footer', 2]],
    ]);

    it('reads no values while a component is missing at any level', function (object $record): void {
        expect(RecordKey::values($this->entityManager(), $record))->toBeNull();
    })->with([
        'a half-filled composite' => fn (): AisleSeat => new AisleSeat('main', 3),
        'a derived identity whose chain is broken' => fn (): CharterSealClause => new CharterSealClause(new CharterSeal(new Charter(null)), 'preamble'),
    ]);
});

describe('grammar', function (): void {
    it('spells the worked keys of the contract, and decodes each back to its values', function (string $class, array $values, string $key): void {
        $em = $this->entityManager();
        $decoded = RecordKey::toIdentifier($em, $class, $key);

        expect(RecordKey::fromValues($em, $class, $values))->toBe($key)
            ->and($decoded)->not->toBeNull()
            ->and(RecordKey::fromValues($em, $class, array_values($decoded ?? [])))->toBe($key);
    })->with([
        'plain strings' => [HostPathRedirect::class, ['plain', 'about'], 'plain~about'],
        'dots, slashes and spaces' => [HostPathRedirect::class, ['www.example.com', '/blog/2026/bundles all the way down'], 'www=2Eexample=2Ecom~=2Fblog=2F2026=2Fbundles=20all=20the=20way=20down'],
        'multi-byte characters and the separator' => [HostPathRedirect::class, ['example.com', '/clanky/Žofia~Návratová'], 'example=2Ecom~=2Fclanky=2F=C5=BDofia=7EN=C3=A1vratov=C3=A1'],
        'the escape introducer and a pipe' => [HostPathRedirect::class, ['a=b', 'c|d'], 'a=3Db~c=7Cd'],
        'a space and a percent-encoding' => [HostPathRedirect::class, ['x y', 'p%2Fq'], 'x=20y~p=252Fq'],
        'two tuples a space join would confuse, first' => [HostPathRedirect::class, ['a b', 'c'], 'a=20b~c'],
        'two tuples a space join would confuse, second' => [HostPathRedirect::class, ['a', 'b c'], 'a~b=20c'],
        'a derived identity' => [StageResidency::class, ['main-hall', 'autumn/2026'], 'main-hall~autumn=2F2026'],
        'a backed enum plus a scalar' => [ShelfPlacement::class, [ShelfZone::Footer, 2], 'footer~2'],
        'a scalar pair' => [OrderLine::class, [1, 2], '1~2'],
        'a single string identifier, verbatim' => [CodeKeyedSlate::class, ['sy/0.1'], 'sy/0.1'],
    ]);
});

describe('mints what the row reads back', function (): void {
    it('mints a new record the key its row will read back, through of() and find() alike', function (object $record, string $key): void {
        $em = $this->entityManager();
        $minted = [RecordKey::of($em, $record), RecordKey::find($em, $record)];

        $this->seed($record);

        $em = $this->entityManager();

        expect($minted)->toBe([$key, $key])
            ->and(RecordKey::toIdentifier($em, $record::class, $key))->not->toBeNull()
            ->and(RecordKey::of($em, $em->getRepository($record::class)->findAll()[0]))->toBe($key);
    })->with([
        'a writer that rewrites the spelling' => fn (): array => [new HexCodedSwatch('0ff'), 'ff'],
        'an integer half typed with leading zeros' => fn (): array => [new SubtypedVoucher('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', '0007', '12.25', false), 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~7~12=2E25~0'],
    ]);

    it('keeps the key of a loaded record over types that convert', function (object $record, string $key): void {
        $this->seed($record);

        $em = $this->entityManager();
        $loaded = $em->getRepository($record::class)->findAll()[0];

        expect(RecordKey::of($em, $loaded))->toBe($key)
            ->and(RecordKey::find($em, $loaded))->toBe($key);
    })->with([
        'a raw-bytes uuid' => fn (): array => [new RawUuidCrate(Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001')), 'a1b2c3d4-0000-4000-8000-000000000001'],
        'a cipher' => fn (): array => [new CipherStrongbox(new Cipher('omega')), 'omega'],
        'a decimal and a boolean' => fn (): array => [new SubtypedVoucher('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', 7, '12.25', true), 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~7~12=2E25~1'],
    ]);

    it('refuses a blank component before its type converts it', function (): void {
        $em = $this->entityManager();
        $voucher = new SubtypedVoucher('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', ' ', '12.25', true);

        expect(RecordKey::find($em, $voucher))->toBeNull()
            ->and(fn (): string => RecordKey::of($em, $voucher))
            ->toThrow(LogicException::class, sprintf('The identifier of [%s] is not a printable value.', SubtypedVoucher::class));
    });
});

describe('shapes', function (): void {
    it('keys, decodes, narrows, correlates, selects, groups and orders every identifier shape', function (array $seed, object $wanted, object $other, string $key, string $otherKey): void {
        $this->seed(...$seed);

        $em = $this->entityManager();
        $class = $wanted::class;
        $found = $em->find($class, RecordKey::toIdentifier($em, $class, $key) ?? []);

        $narrowed = $em->createQueryBuilder()->select('r')->from($class, 'r');
        $narrowed->where(IdentifierConstraint::equals($narrowed, 'r', $class, $key, 'key'));

        $listed = $em->createQueryBuilder()->select('r')->from($class, 'r');
        $listed->where(IdentifierConstraint::in($listed, 'r', $class, [$key, $otherKey, 'not~a~key'], 'keys'));

        $correlated = $em->createQueryBuilder()->select('o')->from($class, 'o');
        $inner = $em->createQueryBuilder()->select('i')->from($class, 'i');
        $inner->where(IdentifierConstraint::correlate($inner, 'i', $class, 'o'));
        $correlated->where(sprintf('EXISTS (%s)', $inner->getDQL()))
            ->andWhere(IdentifierConstraint::equals($correlated, 'o', $class, $key, 'key'));

        $ordered = $em->createQueryBuilder()->from($class, 'r');
        $selection = IdentifierConstraint::select($ordered, 'r', $class);
        $ordered->select($selection->expressions);
        IdentifierConstraint::orderBy($ordered, 'r', $class, 'desc');

        $grouped = $em->createQueryBuilder()->from($class, 'r');
        $grouped->select([...IdentifierConstraint::select($grouped, 'r', $class)->expressions, 'COUNT(r.note) AS notes']);

        foreach (IdentifierConstraint::groupBy($grouped, 'r', $class) as $path) {
            $grouped->addGroupBy($path);
        }

        $keyOf = static fn (object $record): string => RecordKey::of($em, $record);

        expect($found)->not->toBeNull()
            ->and(RecordKey::of($em, $found))->toBe($key)
            ->and(array_map($keyOf, $narrowed->getQuery()->getResult()))->toBe([$key])
            ->and(array_map($keyOf, $listed->getQuery()->getResult()))->toEqualCanonicalizing([$key, $otherKey])
            ->and(array_map($keyOf, $correlated->getQuery()->getResult()))->toBe([$key])
            ->and(array_map($selection->keyOf(...), $ordered->getQuery()->getScalarResult()))->toBe([$otherKey, $key])
            ->and(array_column($grouped->getQuery()->getScalarResult(), 'notes'))->toEqual([1, 1]);
    })->with([
        'three scalar fields' => fn (): array => [[$a = new AisleSeat('main', 3, 7), $b = new AisleSeat('main', 3, 8)], $a, $b, 'main~3~7', 'main~3~8'],
        'an association in the middle' => fn (): array => [[$charter = new Charter(4), $a = new CharterBerth('north', $charter, 1), $b = new CharterBerth('north', $charter, 2)], $a, $b, 'north~4~1', 'north~4~2'],
        'an int-backed enum half' => fn (): array => [[$a = new RackSlot(RackTier::Floor, 'b1'), $b = new RackSlot(RackTier::Top, 'b1')], $a, $b, '1~b1', '3~b1'],
        'an identifier declared on a mapped superclass' => fn (): array => [[$a = new RevisionedNote('terms', 1), $b = new RevisionedNote('terms', 2)], $a, $b, 'terms~1', 'terms~2'],
        'a joined leaf under a composite root' => fn (): array => [[$a = new StatuePlinth('hall', 1), $b = new StatuePlinth('hall', 2)], $a, $b, 'hall~1', 'hall~2'],
        'a single derived identity' => fn (): array => [[$one = new Charter(1), $two = new Charter(2), $a = new CharterSeal($one), $b = new CharterSeal($two)], $a, $b, '1', '2'],
        'a smallint beside an ascii string' => fn (): array => [[$a = new ShortlinkSlot('sy', 7), $b = new ShortlinkSlot('sy', 8)], $a, $b, 'sy~7', 'sy~8'],
        'a uuid beside a ulid' => fn (): array => [
            [
                $a = new UidReceipt(Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001'), new Ulid('01ARZ3NDEKTSV4RRFFQ69G5FAV')),
                $b = new UidReceipt(Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001'), new Ulid('01ARZ3NDEKTSV4RRFFQ69G5FAW')),
            ],
            $a, $b, 'a1b2c3d4-0000-4000-8000-000000000001~01ARZ3NDEKTSV4RRFFQ69G5FAV', 'a1b2c3d4-0000-4000-8000-000000000001~01ARZ3NDEKTSV4RRFFQ69G5FAW',
        ],
    ]);
});

describe('properties', function (): void {
    it('round-trips every generated tuple, decodes only canonical spellings and never throws on client input', function (): void {
        $em = $this->entityManager();
        $random = new Randomizer(new Mt19937((int) (getenv('SYLAMENT_PROPERTY_SEED') ?: 20260928)));
        $iterations = (int) (getenv('SYLAMENT_PROPERTY_ITERATIONS') ?: 40);
        $alphabet = ['a', 'Z', '0', '9', '-', '_', '~', '=', '.', '/', ' ', '%', '|', '(', ',', '<', '"', 'Ž', 'á', '€'];
        $text = static fn (): string => implode('', array_map(static fn (): string => $alphabet[$random->getInt(0, count($alphabet) - 1)], range(1, $random->getInt(1, 12))));
        $generators = [
            HostPathRedirect::class => static fn (): array => [$text(), $text()],
            StageResidency::class => static fn (): array => [$text(), $text()],
            OrderLine::class => static fn (): array => [$random->getInt(-2_000_000, 2_000_000), $random->getInt(0, 999)],
            ShelfPlacement::class => static fn (): array => [$random->getInt(0, 1) === 1 ? ShelfZone::Footer : ShelfZone::Header, $random->getInt(-50, 50)],
            ShortlinkSlot::class => static fn (): array => [str_replace(['Ž', 'á', '€'], 'x', $text()), $random->getInt(-32_768, 32_767)],
            CodeKeyedSlate::class => static fn (): array => [$text()],
        ];
        $widths = ['smallint' => 32_767, 'integer' => 2_147_483_647];
        $widerThanItsColumn = static fn (IdentifierFields $fields, array $values): bool => array_any($values, static fn (mixed $value, int $index): bool => is_int($value) && isset($widths[$fields->types[$index]]) && ($value > $widths[$fields->types[$index]] || $value < -$widths[$fields->types[$index]] - 1));
        $misspellings = [
            mb_strtolower(...),
            mb_strtoupper(...),
            static fn (string $key): string => '0' . $key,
            static fn (string $key): string => $key . '~',
            static fn (string $key): string => mb_substr($key, 0, -1),
            static fn (string $key): string => preg_replace('/[A-Za-z0-9]/', '=41', $key, 1) ?? $key,
            static fn (string $key): string => str_replace('~', '.', $key),
        ];

        foreach ($generators as $class => $generate) {
            $fields = IdentifierFields::of($em, $class);

            for ($iteration = 0; $iteration < $iterations; $iteration++) {
                $values = $generate();

                if (array_any($values, static fn (mixed $value): bool => IdentifierFields::printable($value) === null)) {
                    continue;
                }

                $key = RecordKey::fromValues($em, $class, $values);
                $decoded = RecordKey::toIdentifier($em, $class, $key);

                expect($decoded)->not->toBeNull("[{$class}] refused its own key [{$key}]")
                    ->and(RecordKey::fromValues($em, $class, array_values($decoded ?? [])))->toBe($key);

                if (!$fields->isComposite()) {
                    expect($key)->toBe($values[0]);
                }

                foreach ($misspellings as $misspell) {
                    $misspelled = $misspell($key);
                    $misdecoded = RecordKey::toIdentifier($em, $class, $misspelled);
                    $query = $em->createQueryBuilder()->select('r')->from($class, 'r');

                    expect(IdentifierConstraint::equals($query, 'r', $class, $misspelled, 'key') === null)->toBe($misdecoded === null);

                    if ($misdecoded !== null) {
                        expect(RecordKey::fromValues($em, $class, array_values($misdecoded)))->toBe($misspelled);
                    }
                }

                $client = $random->getBytes($random->getInt(1, 8));
                $query = $em->createQueryBuilder()->select('r')->from($class, 'r');
                IdentifierConstraint::equals($query, 'r', $class, $client, 'a');
                IdentifierConstraint::notEquals($query, 'r', $class, $client, 'b');
                IdentifierConstraint::in($query, 'r', $class, [$client, $key], 'c');
                IdentifierConstraint::notIn($query, 'r', $class, [$client], 'd');
                $clientDecoded = RecordKey::toIdentifier($em, $class, $client);

                if ($clientDecoded !== null && (TestPlatform::current() !== TestPlatform::PostgreSql || !$widerThanItsColumn($fields, array_values($clientDecoded)))) {
                    $em->find($class, $clientDecoded);
                }
            }
        }
    });
});
